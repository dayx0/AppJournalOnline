<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serah terima MPK: akun dengan flag must_change_password (hasil reset
 * massal tahunan) dipaksa mengganti password sebelum bisa membuka halaman
 * apa pun. Rute yang dikecualikan = rute yang justru dibutuhkan untuk
 * menyelesaikan kewajiban itu (ganti password, logout, reset/2FA).
 */
class ForcePasswordChange
{
    /** @var string[] */
    private const array DIIZINKAN = [
        'security.edit',      // halaman ganti password
        'user-password.update',
        'logout',
        'password.confirm',   // RequirePassword gate menuju security.edit
        'password.request',
        'password.email',
        'password.reset',
        'password.update',
        'two-factor.login',
        'verification.notice',
        'verification.verify',
        'well-known.passkeys',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && (bool) $user->must_change_password) {
            $route = $request->route();

            if ($route !== null && in_array($route->getName(), self::DIIZINKAN, true)) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                abort(423, 'Anda wajib mengganti password terlebih dahulu.');
            }

            return redirect()
                ->route('security.edit')
                ->with('warning', 'Ini password sementara serah terima. Buat password barumu dulu sebelum lanjut.');
        }

        return $next($request);
    }
}
