<?php

namespace App\Http\Controllers\Mpk;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $kelasId = $request->user()->kelas_id === null
            ? null
            : (int) $request->user()->kelas_id;

        // MPK tanpa kelas -> semua statistik nol & list kosong.
        $scoped = fn ($q) => $kelasId === null
            ? $q->whereRaw('0 = 1')
            : $q->where('kelas_id', $kelasId);

        // Mendesak = slot menunggu yang tanggalnya sudah lewat (kandidat jam
        // kosong) + pending lama yang belum diputuskan. Keduanya butuh MPK.
        $butuhPerhatian = Journal::with(['guru', 'kelas', 'mataPelajaran'])
            ->whereIn('status', [Journal::STATUS_MENUNGGU, Journal::STATUS_PENDING])
            ->where($scoped)
            ->where(function ($q) {
                $q->whereDate('tanggal', '<', today()->toDateString())
                    ->orWhere('created_at', '<', now()->subDay());
            })
            ->oldest()
            ->take(10)
            ->get();

        return Inertia::render('mpk/dashboard', [
            'stats' => [
                'menunggu' => Journal::where('status', Journal::STATUS_MENUNGGU)->where($scoped)->count(),
                'pending' => Journal::where('status', Journal::STATUS_PENDING)->where($scoped)->count(),
                'divalidasi' => Journal::where('status', Journal::STATUS_DIVALIDASI)->where($scoped)->count(),
                'ditolak' => Journal::where('status', Journal::STATUS_DITOLAK)->where($scoped)->count(),
                'jam_kosong' => Journal::where('status', Journal::STATUS_JAM_KOSONG)->where($scoped)->count(),
                'total' => Journal::where($scoped)->count(),
            ],
            'butuhPerhatian' => $butuhPerhatian,
            'assignedKelas' => $request->user()->kelas?->only(['id', 'nama_kelas']),
        ]);
    }
}
