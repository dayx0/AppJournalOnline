<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response {
        $users = User::with('siswa.kelas:id,nama_kelas')->latest()->paginate(10);
        return Inertia::render('admin/users/index', [
            'users' => $users,
            'kelasOptions' => ClassRoom::orderBy('nama_kelas')->get(['id', 'nama_kelas']),
        ]);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)],
            'role' => 'required|in:admin,guru,mpk',
            'kelas_id' => 'required_if:role,mpk|nullable|integer|exists:kelas,id',
            'nis' => 'nullable|string|max:50|unique:siswa,nis',
        ]);
        $validated['password'] = Hash::make($validated['password']);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
        ]);

        // Akun MPK mendapat satu baris di tabel siswa.
        if ($validated['role'] === 'mpk') {
            Siswa::create([
                'user_id' => $user->id,
                'kelas_id' => $validated['kelas_id'] ?? null,
                'nis' => $validated['nis'] ?? null,
            ]);
        }

        return back()->with('success', 'User berhasil ditambah');
    }

    public function destroy(User $user) {
        // cegah admin menghapus dirinya sendiri
        if($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa hapus akun sendiri');
        }
        $user->delete();
        return back()->with('success', 'User dihapus');
    }

    public function update(Request $request, User $user) {
        $siswaId = $user->siswa?->id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|in:admin,guru,mpk',
            'kelas_id' => 'required_if:role,mpk|nullable|integer|exists:kelas,id',
            'nis' => 'nullable|string|max:50|unique:siswa,nis,'.$siswaId,
            'password' => ['nullable', Password::min(8)],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            ...isset($validated['password']) ? ['password' => $validated['password']] : [],
        ]);

        // Sinkronkan tabel siswa mengikuti role.
        if ($validated['role'] === 'mpk') {
            Siswa::updateOrCreate(
                ['user_id' => $user->id],
                ['kelas_id' => $validated['kelas_id'] ?? null, 'nis' => $validated['nis'] ?? null]
            );
        } else {
            $user->siswa()->delete();
        }

        return back()->with('success', 'User diperbaharui');
    }
}
