<?php

namespace App\Http\Controllers\Mpk;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Services\JournalNotificationService;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(): Response
    {
        // Jurnal pending paling lama dulu agar yang mendesak muncul teratas,
        // lalu revisi terbaru, lalu 10 validasi terakhir sebagai konteks.
        $pending = Journal::with(['guru', 'kelas', 'mataPelajaran'])
            ->where('status', 'pending')
            ->oldest()
            ->take(15)
            ->get();

        $mendesakIds = Journal::where('status', 'pending')
            ->where(function ($q) {
                $q->whereDate('tanggal', '<', today()->toDateString())
                    ->orWhere('created_at', '<', now()->subDay());
            })
            ->pluck('id')
            ->all();

        $revisi = Journal::with(['guru', 'kelas', 'mataPelajaran'])
            ->where('status', 'revisi')
            ->latest()
            ->take(10)
            ->get();

        $terbaru = Journal::with(['guru', 'kelas', 'mataPelajaran', 'validator'])
            ->where('status', 'divalidasi')
            ->latest('validated_at')
            ->take(10)
            ->get();

        return Inertia::render('mpk/notifikasi', [
            'pending' => $pending,
            'mendesakIds' => $mendesakIds,
            'revisi' => $revisi,
            'terbaru' => $terbaru,
            'counts' => [
                'pending' => Journal::where('status', 'pending')->count(),
                'mendesak' => count($mendesakIds),
                'revisi' => Journal::where('status', 'revisi')->count(),
            ],
            'unreadKeys' => JournalNotificationService::unreadKeys(auth()->id()),
        ]);
    }
}
