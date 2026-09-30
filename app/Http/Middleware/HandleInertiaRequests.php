<?php

namespace App\Http\Middleware;

use App\Models\ClassRoom;
use App\Models\Journal;
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
            // Badge = hal yang bisa dikerjakan (bukan inbox):
            // guru = slot hari ini miliknya yang belum diisi,
            // MPK = antrean pending sekelasnya, admin = unread lama.
            // Nama prop dipertahankan agar semua konsumen tidak berubah.
            // Closure = dievaluasi per request (1 query ringan).
            'unreadCount' => fn () => $this->unreadCount($request),
            // Menu Jadwal hanya untuk admin + wali (lihat JadwalPolicy::viewAny).
            'isWali' => fn () => (bool) ($request->user()
                && ClassRoom::where('wali_kelas_id', $request->user()->id)->exists()),
        ];
    }

    private function unreadCount(Request $request): int
    {
        $user = $request->user();

        if (! $user) {
            return 0;
        }

        if ($user->isMpk()) {
            if ($user->kelas_id === null) {
                return 0;
            }

            return Journal::where('kelas_id', $user->kelas_id)
                ->where('status', Journal::STATUS_PENDING)
                ->count();
        }

        if ($user->isGuru()) {
            return Journal::where('guru_id', $user->id)
                ->whereDate('tanggal', today()->toDateString())
                ->where('status', Journal::STATUS_MENUNGGU)
                ->count();
        }

        return UserNotification::forUser($user->id)->unread()->count();
    }
}
