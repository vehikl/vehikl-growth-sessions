<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private const DROPDOWN_LIMIT = 15;

    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'data' => $user->notifications()->latest()->limit(self::DROPDOWN_LIMIT)->get(),
            'unread_count' => $user->unreadNotifications()->count(),
            'dropdown_limit' => self::DROPDOWN_LIMIT,
        ]);
    }

    public function markRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}
