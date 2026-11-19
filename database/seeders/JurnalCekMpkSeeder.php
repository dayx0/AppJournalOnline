<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Jadwal;
use App\Models\Journal;
use App\Models\Subject;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\JournalNotificationService;
use Illuminate\Database\Seeder;

class JurnalCekMpkSeeder extends Seeder
{
    private const MARKER = '[CEK-MPK]';

    /**
     * Skenario simulasi validasi MPK, sinkron dengan jadwal asli.
     *
     * Per kelas, untuk HARI INI:
     * - Slot jadwal pertama  -> diisi guru (status pending) agar MPK bisa
     *   mempraktikkan TERIMA (validasi) atau TOLAK.
     * - Slot jadwal kedua    -> dibiarkan kosong (status menunggu) agar MPK
     *   bisa mempraktikkan TANDAl JAM KOSONG.
     * - Kelas tanpa jadwal hari ini -> 2 jurnal mandiri (1 pending + 1
     *   menunggu) supaya simulasi tetap jalan setiap hari.
     *
     * Idempotent & bisa di-refresh: seed ulang mengembalikan marker
     * [CEK-MPK] ke status awalnya. Data asli user (tanpa marker) tidak
     * pernah disentuh.
     */
    public function run(): void
    {
        $tanggal = today()->toDateString();
        $tanggalObj = today()->copy()->setDate(
            (int) substr($tanggal, 0, 4),
            (int) substr($tanggal, 5, 2),
            (int) substr($tanggal, 8, 2),
        );
        $tanggalObj->locale('id');
        $hari = strtolower($tanggalObj->isoFormat('dddd'));

        // Tanpa ModelEvents saat seeding (lihat DatabaseSeeder), cap
        // tahun+semester diisi eksplisit di sini.
        $tahun = TahunAjaran::query()->where('is_aktif', true)->value('nama')
            ?? TahunAjaran::namaUntukTanggal($tanggal);
        $semester = TahunAjaran::query()->where('is_aktif', true)->value('semester_aktif')
            ?? TahunAjaran::semesterUntukTanggal($tanggal);

        $guruFallback = User::where('email', 'guru@sekolah.test')->first()
            ?? User::where('role', 'guru')->first();

        if (! $guruFallback) {
            $this->command->warn('JurnalCekMpkSeeder: tidak ada user guru, lewati.');

            return;
        }

        $mapelFallback = Subject::first();
        if (! $mapelFallback) {
            $this->command->warn('JurnalCekMpkSeeder: tidak ada mapel, lewati.');

            return;
        }

        $kelasList = ClassRoom::orderBy('id')->get();
        if ($kelasList->isEmpty()) {
            $this->command->warn('JurnalCekMpkSeeder: tidak ada kelas, lewati.');

            return;
        }

        foreach ($kelasList as $kelas) {
            $jadwals = Jadwal::where('kelas_id', $kelas->id)
                ->where('aktif', true)
                ->where('hari', $hari)
                ->whereNotNull('guru_id')
                ->orderBy('jam_mulai')
                ->get();

            if ($jadwals->count() >= 2) {
                $this->siapkanSlotTerisi($jadwals[0], $tanggal, $tahun, $semester);
                $this->siapkanSlotKosong($jadwals[1], $tanggal, $tahun, $semester);
            } elseif ($jadwals->count() === 1) {
                $this->siapkanSlotTerisi($jadwals[0], $tanggal, $tahun, $semester);
                $this->siapkanSlotKosongMandiri($kelas, $guruFallback, $mapelFallback, $tanggal, $tahun, $semester);
            } else {
                $this->siapkanSlotTerisiMandiri($kelas, $guruFallback, $mapelFallback, $tanggal, $tahun, $semester);
                $this->siapkanSlotKosongMandiri($kelas, $guruFallback, $mapelFallback, $tanggal, $tahun, $semester);
            }
        }
    }

    /**
     * Slot terhubung jadwal: pastikan berstatus pending (sudah diisi guru).
     * Slot kosong milik seeder (atau slot yang masih perawan) diisi dengan
     * materi marker. Data asli user tidak ditimpa.
     */
    private function siapkanSlotTerisi(Jadwal $jadwal, string $tanggal, string $tahun, string $semester): void
    {
        $journal = Journal::firstOrCreate(
            ['jadwal_id' => $jadwal->id, 'tanggal' => $tanggal],
            [
                'guru_id' => $jadwal->guru_id,
                'kelas_id' => $jadwal->kelas_id,
                'mapel_id' => $jadwal->mapel_id,
                'tahun_ajaran' => $tahun,
                'semester' => $semester,
                'jam_mulai' => $jadwal->jam_mulai,
                'jam_selesai' => $jadwal->jam_selesai,
                'status' => Journal::STATUS_MENUNGGU,
            ]
        );

        $jadwal->loadMissing(['guru', 'kelas', 'mataPelajaran']);
        $materi = self::MARKER." Materi simulasi {$jadwal->mataPelajaran?->nama_mapel} "
            ."kelas {$jadwal->kelas?->nama_kelas} — sudah diisi {$jadwal->guru?->name}";

        if ($journal->materi === null && $journal->status === Journal::STATUS_MENUNGGU) {
            // Slot perawan: simulasikan guru sudah mengisi.
            $journal->update([
                'materi' => $materi,
                'kegiatan' => 'Kegiatan simulasi: MPK bisa TERIMA atau TOLAK jurnal ini.',
                'catatan' => 'Data testing, boleh dihapus.',
                'status' => Journal::STATUS_PENDING,
            ]);
        } elseif ($this->isMarker($journal->materi) && $journal->status !== Journal::STATUS_PENDING) {
            // Marker pernah diputuskan saat simulasi: kembalikan ke pending.
            $journal->update([
                'materi' => $materi,
                'status' => Journal::STATUS_PENDING,
                'validated_by' => null,
                'validated_at' => null,
                'validation_note' => null,
            ]);
        }

        if ($journal->fresh()->status === Journal::STATUS_PENDING) {
            JournalNotificationService::notifyMpkNewJournal($journal->fresh());
        }
    }

    /**
     * Slot terhubung jadwal: pastikan berstatus menunggu (belum diisi) agar
     * MPK bisa mempraktikkan TANDAI JAM KOSONG. Hanya marker milik seeder
     * yang di-reset; data asli user tidak disentuh.
     */
    private function siapkanSlotKosong(Jadwal $jadwal, string $tanggal, string $tahun, string $semester): void
    {
        $journal = Journal::firstOrCreate(
            ['jadwal_id' => $jadwal->id, 'tanggal' => $tanggal],
            [
                'guru_id' => $jadwal->guru_id,
                'kelas_id' => $jadwal->kelas_id,
                'mapel_id' => $jadwal->mapel_id,
                'tahun_ajaran' => $tahun,
                'semester' => $semester,
                'jam_mulai' => $jadwal->jam_mulai,
                'jam_selesai' => $jadwal->jam_selesai,
                'status' => Journal::STATUS_MENUNGGU,
                'catatan' => self::MARKER.' Slot simulasi — belum diisi (untuk uji tandai jam kosong).',
            ]
        );

        if ($journal->wasRecentlyCreated) {
            return;
        }

        if ($this->isMarker($journal->catatan ?? $journal->materi)
            && $journal->status !== Journal::STATUS_MENUNGGU
        ) {
            $journal->update([
                'materi' => null,
                'kegiatan' => null,
                'catatan' => self::MARKER.' Slot simulasi — belum diisi (untuk uji tandai jam kosong).',
                'status' => Journal::STATUS_MENUNGGU,
                'validated_by' => null,
                'validated_at' => null,
                'validation_note' => null,
            ]);
        }
    }

    /**
     * Jurnal mandiri terisi (fallback kelas tanpa jadwal hari ini).
     * Kunci (guru, kelas, materi) sama seperti seeder lama agar baris lama
     * dipakai ulang, bukan diduplikasi.
     */
    private function siapkanSlotTerisiMandiri(ClassRoom $kelas, User $guru, Subject $mapel, string $tanggal, string $tahun, string $semester): void
    {
        $materi = self::MARKER." Materi uji validasi kelas {$kelas->nama_kelas}";

        $journal = Journal::firstOrCreate(
            [
                'guru_id' => $guru->id,
                'kelas_id' => $kelas->id,
                'materi' => $materi,
            ],
            [
                'mapel_id' => $mapel->id,
                'tanggal' => $tanggal,
                'tahun_ajaran' => $tahun,
                'semester' => $semester,
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '09:30:00',
                'kegiatan' => 'Kegiatan uji silang validasi MPK',
                'catatan' => 'Data testing, boleh dihapus.',
                'status' => Journal::STATUS_PENDING,
                'validated_by' => null,
                'validated_at' => null,
                'validation_note' => null,
            ]
        );

        // Segarkan ke hari ini + kembalikan ke pending bila pernah diputuskan.
        $journal->update([
            'tanggal' => $tanggal,
            'status' => Journal::STATUS_PENDING,
            'validated_by' => null,
            'validated_at' => null,
            'validation_note' => null,
        ]);

        JournalNotificationService::notifyMpkNewJournal($journal->fresh());
    }

    /**
     * Jurnal mandiri kosong (fallback kelas tanpa jadwal hari ini).
     */
    private function siapkanSlotKosongMandiri(ClassRoom $kelas, User $guru, Subject $mapel, string $tanggal, string $tahun, string $semester): void
    {
        $catatan = self::MARKER.' Slot simulasi — belum diisi (untuk uji tandai jam kosong).';

        $journal = Journal::whereNull('jadwal_id')
            ->where('kelas_id', $kelas->id)
            ->where('tanggal', $tanggal)
            ->where('catatan', $catatan)
            ->first();

        if (! $journal) {
            $journal = Journal::create([
                'guru_id' => $guru->id,
                'kelas_id' => $kelas->id,
                'mapel_id' => $mapel->id,
                'tanggal' => $tanggal,
                'tahun_ajaran' => $tahun,
                'semester' => $semester,
                'jam_mulai' => '10:00:00',
                'jam_selesai' => '11:30:00',
                'materi' => null,
                'kegiatan' => null,
                'catatan' => $catatan,
                'status' => Journal::STATUS_MENUNGGU,
            ]);

            return;
        }

        if ($journal->status !== Journal::STATUS_MENUNGGU) {
            $journal->update([
                'materi' => null,
                'kegiatan' => null,
                'status' => Journal::STATUS_MENUNGGU,
                'validated_by' => null,
                'validated_at' => null,
                'validation_note' => null,
            ]);
        }
    }

    private function isMarker(?string $text): bool
    {
        return $text !== null && str_starts_with($text, self::MARKER);
    }
}
