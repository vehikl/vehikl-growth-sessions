<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\GrowthSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GrowthSessionDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public readonly string $title;

    public readonly string $date;

    /**
     * Copies the title and date out of the session rather than keeping the model itself: this
     * notification is queued, and Laravel's queued-job serialization re-fetches Eloquent model
     * properties from the database when the job runs — which fails every time here, since the
     * hard delete has already removed the row by the time this notification is dispatched.
     */
    public function __construct(GrowthSession $growthSession)
    {
        $this->title = $growthSession->title;
        $this->date = $growthSession->date->format('Y-m-d');
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationType::GrowthSessionDeleted->value,
            'title' => $this->title,
            'date' => $this->date,
            'url' => null,
        ];
    }

    /**
     * Without this, the broadcast payload's `type` gets overwritten with this class's fully
     * qualified name — `BroadcastNotificationCreated::broadcastWith()` merges `toArray()` with
     * `['type' => $this->broadcastType()]` last, and the default `broadcastType()` is `get_class()`.
     */
    public function broadcastType(): string
    {
        return NotificationType::GrowthSessionDeleted->value;
    }
}
