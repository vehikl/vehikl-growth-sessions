<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\GrowthSession;

/**
 * Word that a seat has passed down the queue and is now the member's.
 *
 * Queued, and deliberately sent after the promotion is already committed: the seat is theirs
 * whether or not this ever reaches them.
 */
class PromotedFromTheWaitlistNotification extends BaseGrowthSessionNotification
{
    public function __construct(public GrowthSession $growthSession) {}

    public function notificationType(): NotificationType
    {
        return NotificationType::GrowthSessionWaitlistPromotion;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->notificationType()->value,
            'growth_session_id' => $this->growthSession->id,
            'title' => $this->growthSession->title,
            'date' => $this->growthSession->date->format('Y-m-d'),
            'url' => "/growth_sessions/{$this->growthSession->id}",
        ];
    }
}
