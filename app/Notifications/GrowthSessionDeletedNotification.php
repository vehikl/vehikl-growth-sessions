<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\GrowthSession;

class GrowthSessionDeletedNotification extends BaseGrowthSessionNotification
{
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

    public function notificationType(): NotificationType
    {
        return NotificationType::GrowthSessionDeleted;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->notificationType()->value,
            'title' => $this->title,
            'date' => $this->date,
            'url' => null,
        ];
    }
}
