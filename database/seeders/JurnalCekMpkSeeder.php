<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Journal;
use App\Models\Subject;
use App\Models\User;
use App\Services\JournalNotificationService;
use Illuminate\Database\Seeder;

class JurnalCekMpkSeeder extends Seeder
{
    /**
     * Jurnal dummy untuk uji silang validasi MPK per-kelas.
     * Satu jurnal pending per kelas, milik guru@sekolah.test.
     * Marker materi: [CEK-MPK] agar mudah dikenali & di-seed ulang.
     */
    public function run(): void
    {
        $guru = User::where('email', 'guru@sekolah.test')->first()
            ?? User::where('role', 'guru')->first();

        if (! $guru) {
            $this->command->warn('JurnalCekMpkSeeder: tidak ada user guru, lewati.');

            return;
        }

        $mapel = Subject::first();
        if (! $mapel) {
            $this->command->warn('JurnalCekMpkSeeder: tidak ada mapel, lewati.');

            return;
        }

        $kelasList = ClassRoom::orderBy('id')->get();
        if ($kelasList->isEmpty()) {
            $this->command->warn('JurnalCekMpkSeeder: tidak ada kelas, lewati.');

            return;
        }

        foreach ($kelasList as $kelas) {
            $materi = "[CEK-MPK] Materi uji validasi kelas {$kelas->nama_kelas}";

            $journal = Journal::firstOrCreate(
                [
                    'guru_id' => $guru->id,
                    'kelas_id' => $kelas->id,
                    'materi' => $materi,
                ],
                [
                    'mapel_id' => $mapel->id,
                    'tanggal' => today()->toDateString(),
                    'jam_mulai' => '08:00:00',
                    'jam_selesai' => '09:30:00',
                    'kegiatan' => 'Kegiatan uji silang validasi MPK',
                    'catatan' => 'Data testing, boleh dihapus.',
                    'status' => 'pending',
                    'validated_by' => null,
                    'validated_at' => null,
                    'validation_note' => null,
                ]
            );

            // Pastikan status kembali pending jika jurnal marker ini pernah divalidasi manual.
            if (! $journal->wasRecentlyCreated && $journal->status !== 'pending') {
                $journal->update([
                    'status' => 'pending',
                    'validated_by' => null,
                    'validated_at' => null,
                    'validation_note' => null,
                ]);
            }

            // Antrekan notifikasi ke semua MPK (service saat ini broadcast ke semua MPK).
            JournalNotificationService::notifyMpkNewJournal($journal->fresh());
        }
    }
}
