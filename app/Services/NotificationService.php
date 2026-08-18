<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Jobs\ProcessNotifications;
use App\Models\GrowthSession;
use App\Models\User;

class NotificationService
{
    /**
     * Raise one notification naming every event the caller is reporting.
     *
     * Variadic so a single event reads as it always did, while an edit that moved several things
     * at once spreads them into the same call and still produces one notification.
     */
    public static function dispatchNotification(GrowthSession $growthSession, User $initiator, NotificationType ...$eventTypes): void
    {
        // Nothing happened, so there is nothing to tell anybody. A backstop only - callers that
        // build the list from what changed should return before working out an initiator.
        if ($eventTypes === []) {
            return;
        }

        // Both the recipients and the metadata are resolved here, while the growth session is
        // still in the database. By the time the queued job runs, a deletion will have taken
        // the row - and the pivot rows naming its members - with it.
        ProcessNotifications::dispatch(
            $growthSession->id,
            $initiator->id,
            $eventTypes,
            $growthSession->notifiableUserIdsExcludingInitiator($initiator),
            $growthSession->toNotificationMetadata(),
        );
    }
}
