<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Tandai satu/beberapa notifikasi milik user sebagai dibaca.
     */
    public function read(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'keys' => 'required|array|max:50',
            'keys.*' => 'string|max:255',
        ]);

        UserNotification::forUser($request->user()->id)
            ->whereIn('key', $validated['keys'])
            ->unread()
            ->update(['read_at' => now()]);

        return back();
    }

    /**
     * Tandai semua notifikasi milik user sebagai dibaca.
     */
    public function readAll(Request $request): RedirectResponse
    {
        UserNotification::forUser($request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return back();
    }
}
