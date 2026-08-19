<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\GrowthSessionDeleted;
use App\Events\GrowthSessionUpdated;
use App\Models\GrowthSession;
use App\Notifications\GrowthSessionDeletedNotification;
use App\Notifications\GrowthSessionUpdatedNotification;
use Illuminate\Support\Facades\Notification;

class GrowthSessionNotificationSubscriber
{
    private const FIELD_TYPES = [
        'date' => NotificationType::GrowthSessionDateChanged,
        'start_time' => NotificationType::GrowthSessionTimeChanged,
        'location' => NotificationType::GrowthSessionLocationChanged,
    ];

    public function handleUpdated(GrowthSessionUpdated $event): void
    {
        $growthSession = $event->growthSession;

        foreach ($this->meaningfulChanges($growthSession) as ['type' => $type, 'field' => $field, 'old' => $old, 'new' => $new]) {
            Notification::send(
                $growthSession->participantsToNotify($growthSession->owner),
                new GrowthSessionUpdatedNotification($growthSession, $type, $field, $old, $new)
            );
        }
    }

    public function handleDeleted(GrowthSessionDeleted $event): void
    {
        $growthSession = $event->growthSession;

        Notification::send(
            $growthSession->participantsToNotify($growthSession->owner),
            new GrowthSessionDeletedNotification($growthSession)
        );
    }

    /**
     * @return array<int, array{type: NotificationType, field: string, old: mixed, new: mixed}>
     */
    private function meaningfulChanges(GrowthSession $growthSession): array
    {
        $changedFields = array_intersect(array_keys($growthSession->getChanges()), array_keys(self::FIELD_TYPES));

        return array_map(fn (string $field) => [
            'type' => self::FIELD_TYPES[$field],
            'field' => $field,
            'old' => $growthSession->getRawOriginal($field),
            'new' => $growthSession->getAttributes()[$field] ?? null,
        ], array_values($changedFields));
    }
}
