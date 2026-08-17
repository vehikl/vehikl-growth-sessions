<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexNotificationRequest;
use App\Http\Resources\Notification as NotificationResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    /**
     * The signed-in user's most recent notifications, newest first.
     *
     * Defaults to the last 10; `?limit=` widens or narrows that up to the request's max.
     */
    public function index(IndexNotificationRequest $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->notifications()
            ->with(['initiatedBy', 'growthSession'])
            ->latest()
            ->limit($request->limit())
            ->get();

        return NotificationResource::collection($notifications);
    }
}
