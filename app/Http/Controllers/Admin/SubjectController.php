<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(): Response
    {
        $mapels = Subject::latest()->paginate(10);

        return Inertia::render('admin/mapel/index', [
            'mapels' => $mapels,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_mapel' => 'required|string|max:100',
            'kode_mapel' => 'required|string|max:20|unique:mata_pelajaran,kode_mapel',
        ]);
        Subject::create($validated);

        return back()->with('success', 'Mapel berhasil ditambah');
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'nama_mapel' => 'required|string|max:100',
            'kode_mapel' => 'required|string|max:20|unique:mata_pelajaran,kode_mapel,'.$subject->id,
        ]);
        $subject->update($validated);

        return back()->with('success', 'Mapel diperbaharui');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        if ($subject->jurnal()->exists()) {
            return back()->with('error', 'Tidak bisa hapus, Mapel masih dipakai jurnal');
        }

        if ($subject->jadwals()->exists()) {
            return back()->with('error', 'Tidak bisa hapus, Mapel masih dipakai jadwal');
        }

        $subject->delete();

        return back()->with('success', 'Mapel dihapus');
    }
}
