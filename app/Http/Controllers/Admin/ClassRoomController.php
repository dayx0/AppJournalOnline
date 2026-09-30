<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClassRoomController extends Controller
{
    public function index(): Response
    {
        $kelas = ClassRoom::with('waliKelas:id,name')->latest()->paginate(10);

        return Inertia::render('admin/kelas/index', [
            'kelas' => $kelas,
            // Dropdown hanya berisi guru: wali harus guru, bukan admin/MPK.
            'guru' => User::where('role', 'guru')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:50',
            'jurusan' => 'required|string|max:100',
            'tingkat' => 'required|integer|min:1|max:12',
            // Rule::exists + where: wali_kelas_id harus id user ber-role guru.
            'wali_kelas_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'guru')],
        ]);
        ClassRoom::create($validated);

        return back()->with('success', 'Kelas berhasil ditambah');
    }

    public function update(Request $request, ClassRoom $kls)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:50',
            'jurusan' => 'required|string|max:100',
            'tingkat' => 'required|integer|min:1|max:12',
            'wali_kelas_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'guru')],
        ]);
        $kls->update($validated);

        return back()->with('success', 'Kelas diperbaharui');
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
