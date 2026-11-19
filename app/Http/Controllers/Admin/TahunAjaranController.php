<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\TahunAjaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Proses Tahun Ajaran Baru" untuk Model B (akun MPK milik rombel).
 * Tidak ada kelas/akun yang di-rename atau dipindah — hanya arsip,
 * tahun aktif, dan serah terima password.
 */
class TahunAjaranController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/tahun-ajaran/index', $this->data());
    }

    /** Buka tahun baru + kunci total tahun lama. */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:9', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', 'in:ganjil,genap'],
        ]);

        $hasil = TahunAjaranService::bukaBaru($validated['nama'], $validated['semester']);

        return back()->with(
            'success',
            "Tahun {$hasil['baru']->nama} dibuka. {$hasil['jurnal_terkunci']} jurnal dikunci, {$hasil['jadwal_dinonaktifkan']} slot jadwal dinonaktifkan."
        );
    }

    /**
     * Reset password massal semua akun MPK. Kredensial plain dikirim
     * SEKALI via render langsung (tanpa redirect) untuk dicetak/disegel —
     * jangan refresh sebelum dicetak, dan tak pernah disimpan di database.
     */
    public function resetMpk(Request $request): Response
    {
        $kredensial = TahunAjaranService::resetPasswordMpk();

        return Inertia::render('admin/tahun-ajaran/index', [
            ...$this->data(),
            'kredensial' => $kredensial,
        ]);
    }

    /** @return array<string, mixed> */
    private function data(): array
    {
        return [
            'tahuns' => TahunAjaran::orderByDesc('nama')->get(),
            'aktif' => TahunAjaran::aktif(),
            'stats' => [
                'mpk' => User::where('role', 'mpk')->count(),
                'jurnal_terkunci' => Journal::whereNotNull('tahun_ajaran')
                    ->get()
                    ->filter(fn (Journal $j) => $j->isTerkunci())
                    ->count(),
            ],
        ];
    }
}
