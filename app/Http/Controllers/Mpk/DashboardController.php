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
        $user = auth()->user();
        $user->loadMissing('siswa.kelas');
        $kelasId = $user->mpkKelasId();

        // MPK tanpa kelas -> statistik nol dan antrean kosong.
        if (empty($kelasId)) {
            return Inertia::render('mpk/dashboard', [
                'stats' => ['pending' => 0, 'divalidasi' => 0, 'revisi' => 0, 'total' => 0],
                'butuhPerhatian' => [],
                'kelas' => null,
            ]);
        }

        // Mendesak = pending yang tanggal mengajarnya sudah lewat ATAU
        // dibuat lebih dari 24 jam lalu (guru input telat tetap ketangkap).
        $butuhPerhatian = Journal::with(['guru', 'kelas', 'mataPelajaran'])
            ->where('kelas_id', $kelasId)
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
                'pending' => Journal::where('kelas_id', $kelasId)->where('status', 'pending')->count(),
                'divalidasi' => Journal::where('kelas_id', $kelasId)->where('status', 'divalidasi')->count(),
                'revisi' => Journal::where('kelas_id', $kelasId)->where('status', 'revisi')->count(),
                'total' => Journal::where('kelas_id', $kelasId)->count(),
            ],
            'butuhPerhatian' => $butuhPerhatian,
            'kelas' => $user->siswa?->kelas?->only(['id', 'nama_kelas']),
        ]);
    }
}
