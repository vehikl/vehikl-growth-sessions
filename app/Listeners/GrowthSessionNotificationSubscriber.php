<?php

namespace App\Listeners;

use App\Events\GrowthSessionDeleted;
use App\Events\GrowthSessionUpdated;
use App\Models\GrowthSession;
use App\Notifications\GrowthSessionDeletedNotification;
use App\Notifications\GrowthSessionUpdatedNotification;
use Illuminate\Support\Facades\Notification;

class GrowthSessionNotificationSubscriber
{
    /**
     * Only these fields are worth interrupting an attendee's day over — the "when" and "where"
     * of a session. Everything else (title wording, tags, capacity, visibility...) stays silent.
     */
    private const MEANINGFUL_FIELDS = ['date', 'start_time', 'end_time', 'location', 'anydesk_id'];

    public function handleUpdated(GrowthSessionUpdated $event): void
    {
        $growthSession = $event->growthSession;
        $changes = $this->meaningfulChanges($growthSession);

        if (empty($changes)) {
            return;
        }

        Notification::send(
            $growthSession->participantsToNotify(),
            new GrowthSessionUpdatedNotification($growthSession, $changes)
        );
    }

    public function handleDeleted(GrowthSessionDeleted $event): void
    {
        $growthSession = $event->growthSession;

        Notification::send(
            $growthSession->participantsToNotify(),
            new GrowthSessionDeletedNotification($growthSession->title)
        );
    }

    /**
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function meaningfulChanges(GrowthSession $growthSession): array
    {
        $changedFields = array_intersect(array_keys($growthSession->getChanges()), self::MEANINGFUL_FIELDS);

        $changes = [];
        foreach ($changedFields as $field) {
            $changes[$field] = [
                'old' => $growthSession->getRawOriginal($field),
                'new' => $growthSession->getAttributes()[$field] ?? null,
            ];
        }

        return $changes;
    }
}
