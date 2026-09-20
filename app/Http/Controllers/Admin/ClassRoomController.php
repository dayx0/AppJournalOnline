<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassRoomController extends Controller
{
    public function index(): Response {
        $kelas = ClassRoom::latest()->paginate(10);
        return Inertia::render('admin/kelas/index', [
            'kelas' => $kelas
        ]);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:50',
            'jurusan' => 'required|string|max:100',
            'tingkat' => 'required|integer|min:1|max:12',
        ]);
    ClassRoom::create($validated);
    return back()->with('success', 'Kelas berhasil ditambah');
    }

    public function update(Request $request, ClassRoom $kls) {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:50',
            'jurusan' => 'required|string|max:100',
            'tingkat' => 'required|integer|min:1|max:12',
        ]);
        $kls->update($validated);
        return back()->with('success', 'Kelas diperbaharui');
    }

    public function destroy(ClassRoom $kls) {
        if ($kls->jurnal()->exists()) {
            return back()->with('error', 'Tidak bisa hapus, kelas masih dipakai jurnal');
        }
        $kls->delete();
        return back()->with('success', 'Kelas dihapus');
    }
}
