<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Journal;
use App\Models\Subject;
use App\Services\JournalHistoryService;
use App\Services\JournalNotificationService;
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

    public function index()
    {
        $journals = Journal::with(['guru', 'kelas', 'mataPelajaran', 'absensi'])
            ->where('guru_id', auth()->id())
            ->latest()
            ->get();

        return Inertia::render('jurnal/index', [
            'journals' => $journals,
        ]);
    }

    public function create()
    {
        $kelas = ClassRoom::all();
        $mataPelajaran = Subject::all();

        return Inertia::render('jurnal/create', [
            'kelas' => $kelas,
            'mataPelajaran' => $mataPelajaran,
        ]);
    }

    public function edit(Journal $journal)
    {
        $this->ensureOwns($journal);
        $kelas = ClassRoom::all();
        $mataPelajaran = Subject::all();

        return Inertia::render('jurnal/edit', [
            'journal' => $journal->load('absensi'),
            'kelas' => $kelas,
            'mataPelajaran' => $mataPelajaran,
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'mapel_id' => 'required|exists:mata_pelajaran,id',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'materi' => 'required|string',
            'kegiatan' => 'required|string',
            'catatan' => 'nullable|string',
            'hadir' => 'nullable|integer|min:0',
            'izin' => 'nullable|integer|min:0',
            'sakit' => 'nullable|integer|min:0',
            'alpha' => 'nullable|integer|min:0',
        ]);

        $validated['guru_id'] = auth()->id();
        $journal = Journal::create($validated);
        $journal->absensi()->create([
            'hadir' => $validated['hadir'] ?? null,
            'izin' => $validated['izin'] ?? null,
            'sakit' => $validated['sakit'] ?? null,
            'alpha' => $validated['alpha'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jurnal berhasil ditambahkan.']);
        JournalHistoryService::logDibuat($journal, auth()->id());
        JournalNotificationService::notifyMpkNewJournal($journal);

        return redirect()
            ->route('jurnal.index');
    }

    public function update(Request $request, Journal $journal)
    {
        $this->ensureOwns($journal);
        $originalStatus = $journal->status;
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'mapel_id' => 'required|exists:mata_pelajaran,id',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'materi' => 'required|string',
            'kegiatan' => 'required|string',
            'catatan' => 'nullable|string',
            'hadir' => 'nullable|integer|min:0',
            'izin' => 'nullable|integer|min:0',
            'sakit' => 'nullable|integer|min:0',
            'alpha' => 'nullable|integer|min:0',
        ]);

        $journal->update($validated);
        $journal->absensi()->updateOrCreate(
            ['jurnal_id' => $journal->id],
            ['hadir' => $validated['hadir'] ?? null, 'izin' => $validated['izin'] ?? null, 'sakit' => $validated['sakit'] ?? null, 'alpha' => $validated['alpha'] ?? null]
        );

        // Jurnal yang sudah divalidasi/direvisi lalu diubah guru wajib validasi ulang
        if (in_array($originalStatus, ['divalidasi', 'revisi'])) {
            $journal->update([
                'status' => 'pending',
                'validated_by' => null,
                'validated_at' => null,
                'validation_note' => null,
            ]);
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Jurnal diperbarui, perlu validasi ulang oleh MPK.']);
            JournalHistoryService::logResetPending($journal, auth()->id(), $originalStatus);
            JournalNotificationService::notifyResetToPending($journal->fresh());
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Jurnal berhasil diperbarui.']);
        }

        return redirect()
            ->route('jurnal.index');
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
        $originalStatus = $journal->status;
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

        // Perubahan absensi juga membatalkan validasi sebelumnya
        if (in_array($originalStatus, ['divalidasi', 'revisi'])) {
            $journal->update([
                'status' => 'pending',
                'validated_by' => null,
                'validated_at' => null,
                'validation_note' => null,
            ]);
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Absensi disimpan, perlu validasi ulang oleh MPK.']);
            JournalHistoryService::logResetPending($journal, auth()->id(), $originalStatus);
            JournalNotificationService::notifyResetToPending($journal->fresh());
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Absensi berhasil disimpan.']);
        }

        return redirect()
            ->route('jurnal.show', $journal);
    }
}
