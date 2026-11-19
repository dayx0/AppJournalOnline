<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Poin C: akun nonaktif (is_active = false) yang mencoba mengakses route
 * apa pun langsung dipaksa logout (kick) dan dikembalikan ke halaman
 * login dengan pesan error. Berjalan setelah auth route middleware,
 * tapi juga dipasang di grup web sebagai jaring pengaman sesi lama
 * (mis. admin menonaktifkan akun saat user sedang login).
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isAktif()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(403, 'Akun ini sudah tidak aktif.');
            }

            return redirect('/login')->withErrors([
                'email' => 'Akun ini sudah tidak aktif. Hubungi admin sekolah.',
            ]);
        }

        return $next($request);
    }
}
