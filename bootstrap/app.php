<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsGuruOrAdmin;
use App\Http\Middleware\IsMpk;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'isAdmin' => IsAdmin::class,
            'isMpk' => IsMpk::class,
            'isGuruOrAdmin' => IsGuruOrAdmin::class,
            'aktif' => EnsureUserIsActive::class,
            'wajibGantiPassword' => ForcePasswordChange::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            // Jaring pengaman sesi: user() sudah tersedia di grup web
            // (dipakai juga oleh HandleInertiaRequests di atas).
            EnsureUserIsActive::class,
            ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
