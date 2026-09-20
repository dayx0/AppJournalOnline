<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Journal;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class JournalMonitorController extends Controller
{
    public function index(Request $request): Response {
        $query = Journal::with(['guru','kelas','mataPelajaran'])->latest();
        if ($request->filled('search')) {
            $query->where('materi','like', '%' . $request->search . '%');
            }
            $jurnals = $query->paginate(10)->withQueryString();
        return Inertia::render('admin/jurnal/index', [
            'jurnals' => $jurnals    
        ]);
    }
}
