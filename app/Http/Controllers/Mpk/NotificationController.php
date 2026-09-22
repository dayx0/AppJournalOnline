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
        $kelasId = auth()->id() ? auth()->user()->mpkKelasId() : null;

        // MPK tanpa kelas -> semua antrean kosong.
        if (empty($kelasId)) {
            return Inertia::render('mpk/notifikasi', [
                'pending' => [],
                'mendesakIds' => [],
                'revisi' => [],
                'terbaru' => [],
                'counts' => ['pending' => 0, 'mendesak' => 0, 'revisi' => 0],
                'unreadKeys' => JournalNotificationService::unreadKeys(auth()->id()),
            ]);
        }

        // Jurnal pending paling lama dulu agar yang mendesak muncul teratas,
        // lalu revisi terbaru, lalu 10 validasi terakhir sebagai konteks.
        $pending = Journal::with(['guru', 'kelas', 'mataPelajaran'])
            ->where('kelas_id', $kelasId)
            ->where('status', 'pending')
            ->oldest()
            ->take(15)
            ->get();

        $mendesakIds = Journal::where('kelas_id', $kelasId)->where('status', 'pending')
            ->where(function ($q) {
                $q->whereDate('tanggal', '<', today()->toDateString())
                    ->orWhere('created_at', '<', now()->subDay());
            })
            ->pluck('id')
            ->all();

        $revisi = Journal::with(['guru', 'kelas', 'mataPelajaran'])
            ->where('kelas_id', $kelasId)
            ->where('status', 'revisi')
            ->latest()
            ->take(10)
            ->get();

        $terbaru = Journal::with(['guru', 'kelas', 'mataPelajaran', 'validator'])
            ->where('kelas_id', $kelasId)
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
                'pending' => Journal::where('kelas_id', $kelasId)->where('status', 'pending')->count(),
                'mendesak' => count($mendesakIds),
                'revisi' => Journal::where('kelas_id', $kelasId)->where('status', 'revisi')->count(),
            ],
            'unreadKeys' => JournalNotificationService::unreadKeys(auth()->id()),
        ]);
    }
}
