<?php

namespace App\Http\Controllers\Mpk;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // Mendesak = pending yang tanggal mengajarnya sudah lewat ATAU
        // dibuat lebih dari 24 jam lalu (guru input telat tetap ketangkap).
        $butuhPerhatian = Journal::with(['guru', 'kelas', 'mataPelajaran'])
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereDate('tanggal', '<', today()->toDateString())
                    ->orWhere('created_at', '<', now()->subDay());
            })
            ->oldest()
            ->take(10)
            ->get();

        return Inertia::render('mpk/dashboard', [
            'stats' => [
                'pending' => Journal::where('status', 'pending')->count(),
                'divalidasi' => Journal::where('status', 'divalidasi')->count(),
                'revisi' => Journal::where('status', 'revisi')->count(),
                'total' => Journal::count(),
            ],
            'butuhPerhatian' => $butuhPerhatian,
        ]);
    }
}
