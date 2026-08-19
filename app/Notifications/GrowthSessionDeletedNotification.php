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

    public function __construct(public GrowthSession $growthSession) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationType::GrowthSessionDeleted->value,
            'title' => $this->growthSession->title,
            'date' => $this->growthSession->date->format('Y-m-d'),
            'url' => null,
        ];
    }
}
