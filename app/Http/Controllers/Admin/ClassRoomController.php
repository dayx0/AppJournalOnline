<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Jurusan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClassRoomController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'tingkat' => 'nullable|in:semua,10,11,12',
            'jurusan_id' => 'nullable|integer|exists:jurusans,id',
        ]);

        $tingkat = $validated['tingkat'] ?? 'semua';
        $jurusanId = $validated['jurusan_id'] ?? null;

        $query = ClassRoom::with(['waliKelas:id,name', 'jurusan:id,kode,nama,kelompok']);

        if ($tingkat !== 'semua') {
            $query->where('tingkat', (int) $tingkat);
        }

        if ($jurusanId !== null) {
            $query->where('jurusan_id', $jurusanId);
        }

        $kelas = $query->latest()->paginate(10)->withQueryString();

        $counts = [
            'semua' => ClassRoom::count(),
            '10' => ClassRoom::where('tingkat', 10)->count(),
            '11' => ClassRoom::where('tingkat', 11)->count(),
            '12' => ClassRoom::where('tingkat', 12)->count(),
        ];

        return Inertia::render('admin/kelas/index', [
            'kelas' => $kelas,
            // Dropdown hanya berisi guru: wali harus guru, bukan admin/MPK.
            'guru' => User::where('role', 'guru')->orderBy('name')->get(['id', 'name']),
            'jurusans' => Jurusan::orderBy('kode')->get(['id', 'kode', 'nama', 'kelompok']),
            'allowedByTingkat' => Jurusan::ALLOWED_BY_TINGKAT,
            'filters' => [
                'tingkat' => $tingkat,
                'jurusan_id' => $jurusanId !== null ? (string) $jurusanId : null,
            ],
            'counts' => $counts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:50|unique:kelas,nama_kelas',
            'jurusan_id' => 'required|exists:jurusans,id',
            'tingkat' => 'required|integer|min:1|max:12',
            // Rule::exists + where: wali_kelas_id harus id user ber-role guru.
            'wali_kelas_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'guru')],
        ]);
        $this->assertJurusanBerlaku((int) $validated['jurusan_id'], (int) $validated['tingkat']);
        ClassRoom::create($validated);

        return back()->with('success', 'Kelas berhasil ditambah');
    }

    public function update(Request $request, ClassRoom $kls)
    {
        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:50', Rule::unique('kelas', 'nama_kelas')->ignore($kls->id)],
            'jurusan_id' => 'required|exists:jurusans,id',
            'tingkat' => 'required|integer|min:1|max:12',
            'wali_kelas_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'guru')],
        ]);
        $this->assertJurusanBerlaku((int) $validated['jurusan_id'], (int) $validated['tingkat']);
        $kls->update($validated);

        return back()->with('success', 'Kelas diperbaharui');
    }

    /**
     * Jurusan harus berlaku untuk tingkatnya (mis. X tidak boleh AK/BD/RPL).
     * Tingkat di luar 10-12 (data lama) tidak dibatasi.
     */
    private function assertJurusanBerlaku(int $jurusanId, int $tingkat): void
    {
        $jurusan = Jurusan::findOrFail($jurusanId);

        if (! Jurusan::isAllowedForTingkat($jurusan->kode, $tingkat)) {
            $boleh = implode(', ', Jurusan::allowedCodesForTingkat($tingkat));

            throw ValidationException::withMessages([
                'jurusan_id' => "Jurusan {$jurusan->kode} tidak berlaku untuk tingkat {$tingkat} (boleh: {$boleh}).",
            ]);
        }
    }

    public function destroy(ClassRoom $kls)
    {
        if ($kls->jurnal()->exists()) {
            return back()->with('error', 'Tidak bisa hapus, kelas masih dipakai jurnal');
        }
        $kls->delete();

        return back()->with('success', 'Kelas dihapus');
    }
}
