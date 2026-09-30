<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Siswa;
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
        $users = User::with(['kelas:id,nama_kelas', 'siswa:id,user_id,kelas_id,nis'])->latest()->paginate(10);

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
            // Nullable (bukan required): MPK boleh dibuat dulu, ditempatkan
            // belakangan. MPK tanpa kelas = tidak bisa validasi apa pun.
            'kelas_id' => 'nullable|exists:kelas,id',
            'nis' => 'nullable|string|max:50|unique:siswa,nis',
        ]);
        $validated['password'] = Hash::make($validated['password']);
        if (($validated['role'] ?? null) !== 'mpk') {
            $validated['kelas_id'] = null;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'kelas_id' => $validated['kelas_id'] ?? null,
        ]);

        // Akun MPK mendapat satu baris profil di tabel siswa (NIS +
        // cermin kelas). Penempatan resmi tetap users.kelas_id.
        if ($validated['role'] === 'mpk') {
            Siswa::create([
                'user_id' => $user->id,
                'kelas_id' => $validated['kelas_id'] ?? null,
                'nis' => $validated['nis'] ?? null,
            ]);
        }

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
        $siswaId = $user->siswa?->id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|in:admin,guru,mpk',
            'password' => ['nullable', Password::min(8)],
            'kelas_id' => 'nullable|exists:kelas,id',
            'nis' => 'nullable|string|max:50|unique:siswa,nis,'.$siswaId,
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (($validated['role'] ?? $user->role) !== 'mpk') {
            $validated['kelas_id'] = null;
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'kelas_id' => $validated['kelas_id'] ?? null,
            ...isset($validated['password']) ? ['password' => $validated['password']] : [],
        ]);

        // Sinkronkan profil siswa mengikuti role (cermin kelas + NIS).
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
