<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subject;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(): Response {
        $mapels = Subject::latest()->paginate(10);
        return Inertia::render('admin/mapel/index', [
            'mapels' => $mapels
        ]);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'nama_mapel' => 'required|string|max:100',
            'kode_mapel' => 'required|string|max:20|unique:mata_pelajaran,kode_mapel',
        ]);
        Subject::create($validated);
        return back()->with('success', 'Mapel berhasil ditambah');    
    }

    public function update(Request $request, Subject $subject) {
        $validated = $request->validate([
            'nama_mapel' => 'required|string|max:100',
            'kode_mapel' => 'required|string|max:20|unique:mata_pelajaran,kode_mapel,'.$subject->id,
        ]);
        $subject->update($validated);
        return back()->with('success', 'Mapel diperbaharui');
    }

    public function destroy(Subject $subject) {
        if ($subject->jurnal()->exists()) {
            return back()->with('error', 'Tidak bisa hapus, Mapel masih dipakai jurnal');
        }
        $subject->delete();
        return back()->with('success', 'Mapel dihapus');
    }
}
