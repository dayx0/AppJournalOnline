<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response {
        $users = User::latest()->paginate(10);
        return Inertia::render('admin/users/index', [
            'users' => $users
        ]);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)],
            'role' => 'required|in:admin,guru,mpk',
        ]);
        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|in:admin,guru,mpk',
            'password' => ['nullable', Password::min(8)],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        return back()->with('success', 'User diperbaharui');
    }
}
