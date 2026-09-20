<?php

namespace App\Http\Middleware;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            // Badge unread beneran dari tabel user_notifications (1 query ringan).
            // Closure = dievaluasi per request; filter label tetap pakai
            // counts per halaman agar tidak membebani setiap navigasi.
            'unreadCount' => fn () => $this->unreadCount($request),
        ];
    }

    private function unreadCount(Request $request): int
    {
        $user = $request->user();

        if (! $user) {
            return 0;
        }

        return UserNotification::forUser($user->id)->unread()->count();
    }
}
