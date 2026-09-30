<?php

namespace App\Http\Controllers\Mpk;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Services\JournalNotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $kelasId = $request->user()->kelas_id === null
            ? null
            : (int) $request->user()->kelas_id;

        // Kunci: semua list dibatasi ke kelas MPK. Tanpa kelas -> kosong.
        $scoped = fn ($q) => $kelasId === null
            ? $q->whereRaw('0 = 1')
            : $q->where('kelas_id', $kelasId);

        // Timeline 2-in-1 = jadwal kelas HARI INI + panel eksekusi.
        // Semua list dibatasi tanggal hari ini (tanggal lain lewat
        // halaman Validasi dengan filter tanggal). Mendesak tetap global
        // (slot telat dari hari sebelumnya).
        $hariIni = today()->toDateString();

        // Jurnal pending paling lama dulu agar yang mendesak muncul teratas,
        // lalu slot menunggu (kandidat jam kosong), ditolak, dan jam_kosong
        // terbaru sebagai konteks pengawasan.
        $pending = Journal::with(['guru', 'kelas', 'mataPelajaran'])
            ->where('status', 'pending')
            ->where($scoped)
            ->whereDate('tanggal', $hariIni)
            ->oldest()
            ->take(15)
            ->get();

        $mendesakIds = Journal::where('status', 'pending')
            ->where($scoped)
            ->where(function ($q) {
                $q->whereDate('tanggal', '<', today()->toDateString())
                    ->orWhere('created_at', '<', now()->subDay());
            })
            ->pluck('id')
            ->all();

        $menunggu = Journal::with(['guru', 'kelas', 'mataPelajaran'])
            ->where('status', Journal::STATUS_MENUNGGU)
            ->where($scoped)
            ->whereDate('tanggal', $hariIni)
            ->oldest()
            ->take(15)
            ->get();

        $ditolak = Journal::with(['guru', 'kelas', 'mataPelajaran'])
            ->where('status', Journal::STATUS_DITOLAK)
            ->where($scoped)
            ->whereDate('tanggal', $hariIni)
            ->latest()
            ->take(10)
            ->get();

        $terbaru = Journal::with(['guru', 'kelas', 'mataPelajaran', 'validator'])
            ->where('status', 'divalidasi')
            ->where($scoped)
            ->whereDate('tanggal', $hariIni)
            ->latest('validated_at')
            ->take(10)
            ->get();

        return Inertia::render('mpk/notifikasi', [
            'pending' => $pending,
            'mendesakIds' => $mendesakIds,
            'menunggu' => $menunggu,
            'ditolak' => $ditolak,
            'terbaru' => $terbaru,
            'counts' => [
                'pending' => Journal::where('status', 'pending')->where($scoped)->whereDate('tanggal', $hariIni)->count(),
                'mendesak' => count($mendesakIds),
                'menunggu' => Journal::where('status', Journal::STATUS_MENUNGGU)->where($scoped)->whereDate('tanggal', $hariIni)->count(),
                'ditolak' => Journal::where('status', Journal::STATUS_DITOLAK)->where($scoped)->whereDate('tanggal', $hariIni)->count(),
            ],
            'unreadKeys' => JournalNotificationService::unreadKeys(auth()->id()),
            'assignedKelas' => $request->user()->kelas?->only(['id', 'nama_kelas']),
        ]);
    }
}
