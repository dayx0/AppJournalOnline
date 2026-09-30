<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\JournalHistory;
use App\Services\JournalHistoryService;
use App\Services\JournalNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JournalController extends Controller
{
    private function ensureOwns(Journal $journal): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        } // admin boleh semua, hapus baris ini kalau admin tidak boleh
        abort_if($journal->guru_id !== auth()->id(), 403);
    }

    /**
     * Penegak "isi hanya hari ini": arsip tanggal lain read-only untuk guru.
     * Admin dikecualikan (koreksi data). Frontend menyembunyikan tombol,
     * tapi server yang memutuskan (tombol bisa diakali via API langsung).
     */
    private function ensureHariIni(Journal $journal): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }
        abort_unless(
            $journal->tanggal === today()->toDateString(),
            422, 'Hanya slot hari ini yang bisa diisi.'
        );
    }

    public function index(Request $request)
    {
        $request->validate(['tanggal' => 'nullable|date']);

        $guruId = auth()->id();
        $sekarang = now();

        // Jendela geser H-1 s.d H+1: di luar itu kembali ke hari ini.
        // Melihat boleh, mengisi tetap hanya hari ini (dijaga update()).
        $diminta = $request->filled('tanggal')
            ? Carbon::parse($request->tanggal)->toDateString()
            : $sekarang->toDateString();
        $min = $sekarang->copy()->subDay()->toDateString();
        $maks = $sekarang->copy()->addDay()->toDateString();
        $tanggal = ($diminta < $min || $diminta > $maks) ? $sekarang->toDateString() : $diminta;

        // SEMUA slot pada tanggal aktif milik guru (bukan cuma menunggu):
        // supaya tampil seperti jadwal harian — tiap baris jam tahu nasibnya.
        $slotHariIni = Journal::with(['kelas:id,nama_kelas', 'mataPelajaran:id,nama_mapel'])
            ->where('guru_id', $guruId)
            ->whereDate('tanggal', $tanggal)
            ->orderBy('jam_mulai')
            ->get();

        $journals = Journal::with(['guru', 'kelas', 'mataPelajaran', 'absensi'])
            ->where('guru_id', $guruId)
            ->latest()
            ->get();

        return Inertia::render('jurnal/index', [
            'journals' => $journals,
            'slotHariIni' => $slotHariIni,
            // Waktu server (WIB, bukan jam HP) agar penanda "sedang
            // berlangsung" konsisten untuk semua user.
            'sekarang' => $sekarang->format('H:i'),
            'labelHari' => Carbon::parse($tanggal)->locale('id')->isoFormat('dddd, D MMMM YYYY'),
            'tanggalAktif' => $tanggal,
            // Acuan "hari ini" versi server: tombol Isi hanya aktif bila
            // tanggalAktif sama dengan ini (lihat guard ensureHariIni).
            'tanggalHariIni' => $sekarang->toDateString(),
        ]);
    }

    public function edit(Journal $journal)
    {
        $this->ensureOwns($journal);

        // Halaman edit hanya mengisi materi/kegiatan (identitas slot
        // read-only dari jadwal), jadi tidak perlu kirim daftar kelas/mapel.
        return Inertia::render('jurnal/edit', [
            'journal' => $journal->load(['absensi', 'kelas:id,nama_kelas', 'mataPelajaran:id,nama_mapel']),
        ]);
    }

    public function show(Journal $journal)
    {
        $this->ensureOwns($journal);
        $journal->load(['guru', 'kelas', 'mataPelajaran', 'absensi', 'validator']);
        // Membuka detail = sudah melihat isi notifikasi jurnal ini
        JournalNotificationService::markJournalRead(auth()->id(), $journal->id);

        return Inertia::render('jurnal/show', [
            'journal' => $journal,
            'histories' => $journal->histories()->with('actor:id,name')->oldest()->get(),
            // Belum ada tabel siswa di database, sehingga daftar
            // kehadiran per nama siswa belum tersedia.
            'students' => [],
        ]);
    }

    public function update(Request $request, Journal $journal)
    {
        $this->ensureOwns($journal); // pola lama: admin bypass, guru harus pemilik
        $this->ensureHariIni($journal);

        abort_if($journal->status === Journal::STATUS_IZIN, 422, 'Slot dispensasi tidak bisa diisi.');
        abort_if(in_array($journal->status, [Journal::STATUS_DIVALIDASI, Journal::STATUS_DITOLAK]), 422, 'Jurnal sudah final.');

        $validated = $request->validate([
            'materi' => 'required|string',
            'kegiatan' => 'required|string',
            'catatan' => 'nullable|string',
            'hadir' => 'nullable|integer|min:0',
            'izin' => 'nullable|integer|min:0',
            'sakit' => 'nullable|integer|min:0',
            'alpha' => 'nullable|integer|min:0',
        ]);

        $dari = $journal->status;
        // Inti susulan: mengisi slot jam_kosong menghasilkan terlambat, bukan pending.
        $ke = $dari === Journal::STATUS_JAM_KOSONG
            ? Journal::STATUS_TERLAMBAT
            : Journal::STATUS_PENDING;

        $journal->update([...$validated, 'status' => $ke,
            'validated_by' => null, 'validated_at' => null, 'validation_note' => null]);
        $journal->absensi()->updateOrCreate(
            ['jurnal_id' => $journal->id],
            [
                'hadir' => $validated['hadir'] ?? null,
                'izin' => $validated['izin'] ?? null,
                'sakit' => $validated['sakit'] ?? null,
                'alpha' => $validated['alpha'] ?? null,
            ]
        );

        JournalHistoryService::log($journal->fresh(), auth()->id(),
            $ke === Journal::STATUS_TERLAMBAT ? JournalHistory::AKSI_TERLAMBAT : JournalHistory::AKSI_DIISI,
            $dari, $ke);
        // Susulan pun perlu diketahui MPK kelasnya:
        JournalNotificationService::notifyMpkNewJournal($journal->fresh());

        return redirect()->route('jurnal.index');
    }

    public function destroy(Journal $journal)
    {
        $this->ensureOwns($journal);
        JournalNotificationService::clearAllForJournal($journal->id);
        $journal->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jurnal berhasil dihapus.']);

        return redirect()
            ->route('jurnal.index');
    }

    public function absensi(Journal $journal)
    {
        $this->ensureOwns($journal);
        $journal->load(['kelas', 'mataPelajaran', 'absensi']);

        return Inertia::render('jurnal/absensi', [
            'journal' => $journal,
        ]);
    }

    public function updateAbsensi(Request $request, Journal $journal)
    {
        $this->ensureOwns($journal);
        // Konsisten dengan update(): jurnal final dan slot izin tidak bisa diubah.
        abort_if($journal->status === Journal::STATUS_IZIN, 422, 'Slot dispensasi tidak bisa diubah.');
        abort_if(in_array($journal->status, [Journal::STATUS_DIVALIDASI, Journal::STATUS_DITOLAK]), 422, 'Jurnal sudah final.');

        $validated = $request->validate([
            'hadir' => 'nullable|integer|min:0',
            'izin' => 'nullable|integer|min:0',
            'sakit' => 'nullable|integer|min:0',
            'alpha' => 'nullable|integer|min:0',
        ]);

        $journal->absensi()->updateOrCreate(
            ['jurnal_id' => $journal->id],
            ['hadir' => $validated['hadir'] ?? null, 'izin' => $validated['izin'] ?? null, 'sakit' => $validated['sakit'] ?? null, 'alpha' => $validated['alpha'] ?? null]
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Absensi berhasil disimpan.']);

        return redirect()
            ->route('jurnal.show', $journal);
    }

    public function tandaiIzin(Request $request, Journal $journal)
    {
        $this->ensureOwns($journal);
        $this->ensureHariIni($journal);
        abort_unless($journal->status === Journal::STATUS_MENUNGGU, 422, 'Hanya slot menunggu.');
        $validated = $request->validate(['catatan' => 'required|string|max:1000']);
        $journal->update(['status' => Journal::STATUS_IZIN,
            'validation_note' => $validated['catatan'],
            'validated_by' => auth()->id(), 'validated_at' => now()]);
        JournalHistoryService::log($journal->fresh(), auth()->id(),
            JournalHistory::AKSI_IZIN, Journal::STATUS_MENUNGGU, Journal::STATUS_IZIN, $validated['catatan']);

        return back();
    }
}
