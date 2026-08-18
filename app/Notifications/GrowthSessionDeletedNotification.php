<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GrowthSessionDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $growthSessionTitle) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'growth_session_deleted',
            'title' => $this->growthSessionTitle,
            'message' => $this->message(),
            'url' => null,
        ];
    }

    private function message(): string
    {
        return "{$this->growthSessionTitle} was cancelled";
    }
}
