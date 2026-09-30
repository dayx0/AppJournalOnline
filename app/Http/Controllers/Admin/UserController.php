<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::with('kelas:id,nama_kelas')->latest()->paginate(10);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'kelas' => ClassRoom::orderBy('nama_kelas')->get(['id', 'nama_kelas']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)],
            'role' => 'required|in:admin,guru,mpk',
            // Hanya relevan untuk MPK; nullable agar MPK bisa dibuat dulu
            // baru ditempatkan ke kelas. Admin/guru diabaikan (diset null).
            'kelas_id' => 'nullable|exists:kelas,id',
        ]);
        $validated['password'] = Hash::make($validated['password']);
        if (($validated['role'] ?? null) !== 'mpk') {
            $validated['kelas_id'] = null;
        }
        User::create($validated);

        return back()->with('success', 'User berhasil ditambah');
    }

    public function destroy(User $user)
    {
        // cegah admin menghapus dirinya sendiri
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa hapus akun sendiri');
        }
        $user->delete();

        return back()->with('success', 'User dihapus');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|in:admin,guru,mpk',
            'password' => ['nullable', Password::min(8)],
            'kelas_id' => 'nullable|exists:kelas,id',
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (($validated['role'] ?? $user->role) !== 'mpk') {
            $validated['kelas_id'] = null;
        }

        $user->update($validated);

        return back()->with('success', 'User diperbaharui');
    }
}
