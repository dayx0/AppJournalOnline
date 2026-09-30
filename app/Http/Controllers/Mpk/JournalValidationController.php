<?php

namespace App\Http\Controllers\Mpk;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Services\JournalHistoryService;
use App\Services\JournalNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalValidationController extends Controller
{
    /**
     * Ambil kelas yang ditempati MPK yang sedang login.
     * Null = MPK belum ditempati -> tidak boleh lihat apapun.
     */
    private function mpkKelasId(Request $request): ?int
    {
        $kelasId = $request->user()->kelas_id;

        return $kelasId === null ? null : (int) $kelasId;
    }

    /** Tolak akses ke jurnal di luar kelas MPK dengan 403. */
    private function ensureMpkOwns(Request $request, Journal $journal): void
    {
        abort_unless($request->user()->canValidateJournal($journal), 403);
    }

    public function index(Request $request): Response
    {
        $request->validate(['tanggal' => 'nullable|date']);
        $kelasId = $this->mpkKelasId($request);

        // Jendela H-1 s.d H+1 seperti halaman guru; di luar itu hari ini.
        $diminta = $request->filled('tanggal')
            ? Carbon::parse($request->tanggal)->toDateString()
            : today()->toDateString();
        $min = today()->subDay()->toDateString();
        $maks = today()->addDay()->toDateString();
        $tanggal = ($diminta < $min || $diminta > $maks) ? today()->toDateString() : $diminta;

        $query = Journal::with(['guru', 'kelas', 'mataPelajaran', 'validator'])
            ->when($kelasId === null, fn ($q) => $q->whereRaw('0 = 1'))
            ->when($kelasId !== null, fn ($q) => $q->where('kelas_id', $kelasId))
            ->whereDate('tanggal', $tanggal)
            ->latest();

        if ($request->filled('status') && in_array($request->status, [
            Journal::STATUS_MENUNGGU,
            Journal::STATUS_PENDING,
            Journal::STATUS_DIVALIDASI,
            Journal::STATUS_DITOLAK,
            Journal::STATUS_JAM_KOSONG,
            Journal::STATUS_IZIN,
            Journal::STATUS_TERLAMBAT,
        ], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('materi', 'like', "%$s%")
                    ->orWhereHas('guru', fn ($qq) => $qq->where('name', 'like', "%$s%"))
                    ->orWhereHas('kelas', fn ($qq) => $qq->where('nama_kelas', 'like', "%$s%"))
                    ->orWhereHas('mataPelajaran', fn ($qq) => $qq->where('nama_mapel', 'like', "%$s%"));
            });
        }

        return Inertia::render('mpk/jurnal/index', [
            'jurnals' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['status', 'search']),
            'tanggalAktif' => $tanggal,
            'assignedKelas' => $request->user()->kelas?->only(['id', 'nama_kelas']),
        ]);
    }

    public function show(Request $request, Journal $journal): Response
    {
        $this->ensureMpkOwns($request, $journal);

        $journal->load(['guru', 'kelas', 'mataPelajaran', 'absensi', 'validator']);
        // MPK membuka detail = antrean jurnal ini sudah dilihat
        JournalNotificationService::markJournalRead(auth()->id(), $journal->id);

        return Inertia::render('mpk/jurnal/show', [
            'journal' => $journal,
            'histories' => $journal->histories()->with('actor:id,name')->oldest()->get(),
        ]);
    }

    public function validate(Request $request, Journal $journal)
    {
        $this->ensureMpkOwns($request, $journal);
        // Hanya jurnal yang sudah diisi guru yang bisa divalidasi.
        // Slot menunggu/izin/kosong tidak punya isi untuk dinilai.
        abort_unless($journal->status === Journal::STATUS_PENDING, 422, 'Hanya jurnal terisi yang bisa divalidasi.');

        $validated = $request->validate([
            'validation_note' => 'nullable|string|max:1000',
        ]);
        $dari = $journal->status;

        $journal->update([
            'status' => 'divalidasi',
            'validated_by' => $request->user()->id,
            'validated_at' => now(),
            'validation_note' => $validated['validation_note'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jurnal divalidasi.']);
        $fresh = $journal->fresh();
        JournalHistoryService::logKeputusan($fresh, $request->user()->id, $dari);
        JournalNotificationService::notifyGuruDecision($fresh);

        return back();
    }

    public function tolak(Request $request, Journal $journal)
    {
        $this->ensureMpkOwns($request, $journal); // scope kelas kemarin, jangan diubah
        abort_unless($journal->status === Journal::STATUS_PENDING, 422, 'Hanya jurnal terisi.');
        // Catatan WAJIB: vonis ketidakhadiran harus beralasan, beda dengan validasi.
        $validated = $request->validate(['validation_note' => 'required|string|max:1000']);
        $dari = $journal->status;
        $journal->update(['status' => Journal::STATUS_DITOLAK, 'validated_by' => $request->user()->id,
            'validated_at' => now(), 'validation_note' => $validated['validation_note']]);
        $fresh = $journal->fresh();
        JournalHistoryService::logKeputusan($fresh, $request->user()->id, $dari);
        JournalNotificationService::notifyGuruDecision($fresh);

        return back();
    }

    public function tandaiKosong(Request $request, Journal $journal)
    {
        $this->ensureMpkOwns($request, $journal);
        // Guard berlapis dengan pesan spesifik: user harus paham KENAPA ditolak.
        abort_if($journal->status === Journal::STATUS_IZIN, 422, 'Slot dispensasi resmi.');
        abort_unless($journal->status === Journal::STATUS_MENUNGGU, 422, 'Hanya slot belum diisi.');
        $validated = $request->validate(['validation_note' => 'nullable|string|max:1000']);
        $dari = $journal->status;

        $journal->update([
            'status' => Journal::STATUS_JAM_KOSONG,
            'validated_by' => $request->user()->id,
            'validated_at' => now(),
            'validation_note' => $validated['validation_note'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Slot ditandai jam kosong.']);
        $fresh = $journal->fresh();
        JournalHistoryService::logKeputusan($fresh, $request->user()->id, $dari);
        // Guru wajib tahu slotnya dinyatakan kosong (kalau tidak, ia tak akan
        // pernah mengisi susulan). notifyGuruDecision memetakan jam_kosong.
        JournalNotificationService::notifyGuruDecision($fresh);

        return back();
    }
}
