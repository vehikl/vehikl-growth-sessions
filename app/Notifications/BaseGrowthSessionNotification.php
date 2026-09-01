<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Notifications\Notification;

abstract class BaseGrowthSessionNotification extends Notification
{
    abstract public function notificationType(): NotificationType;

    public function via(): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastType(): string
    {
        return $this->notificationType()->value;
    }
}
