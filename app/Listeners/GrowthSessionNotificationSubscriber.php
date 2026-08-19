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
        'end_time' => NotificationType::GrowthSessionTimeChanged,
        'location' => NotificationType::GrowthSessionLocationChanged,
    ];

    public function handleUpdated(GrowthSessionUpdated $event): void
    {
        $growthSession = $event->growthSession;

        foreach ($this->meaningfulChangesByType($growthSession) as ['type' => $type, 'changes' => $changes]) {
            Notification::send(
                $growthSession->participantsToNotify($growthSession->owner),
                new GrowthSessionUpdatedNotification($growthSession, $type, $changes)
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
     * @return array<string, array{type: NotificationType, changes: array<string, array{old: mixed, new: mixed}>}>
     */
    private function meaningfulChangesByType(GrowthSession $growthSession): array
    {
        $changedFields = array_intersect(array_keys($growthSession->getChanges()), array_keys(self::FIELD_TYPES));

        $grouped = [];
        foreach ($changedFields as $field) {
            $type = self::FIELD_TYPES[$field];
            $grouped[$type->value]['type'] = $type;
            $grouped[$type->value]['changes'][$field] = [
                'old' => $growthSession->getRawOriginal($field),
                'new' => $growthSession->getAttributes()[$field] ?? null,
            ];
        }

        return $grouped;
    }
}
