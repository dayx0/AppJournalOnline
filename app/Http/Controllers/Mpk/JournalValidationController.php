<?php

namespace App\Http\Controllers\Mpk;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Services\JournalHistoryService;
use App\Services\JournalNotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalValidationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Journal::with(['guru', 'kelas', 'mataPelajaran', 'validator'])->latest();

        if ($request->filled('status') && in_array($request->status, ['pending', 'divalidasi', 'revisi'])) {
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
        ]);
    }

    public function show(Journal $journal): Response
    {
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

    public function revisi(Request $request, Journal $journal)
    {
        $validated = $request->validate([
            'validation_note' => 'required|string|max:1000',
        ]);
        $dari = $journal->status;

        $journal->update([
            'status' => 'revisi',
            'validated_by' => $request->user()->id,
            'validated_at' => now(),
            'validation_note' => $validated['validation_note'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jurnal dikembalikan untuk revisi.']);
        $fresh = $journal->fresh();
        JournalHistoryService::logKeputusan($fresh, $request->user()->id, $dari);
        JournalNotificationService::notifyGuruDecision($fresh);

        return back();
    }
}
