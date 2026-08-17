<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Jobs\ProcessNotifications;
use App\Models\GrowthSession;
use App\Models\User;

class NotificationService
{
    public static function dispatchNotification(GrowthSession $growthSession, User $initiator, NotificationType $notificationType): void
    {
        // Both the recipients and the metadata are resolved here, while the growth session is
        // still in the database. By the time the queued job runs, a deletion will have taken
        // the row - and the pivot rows naming its members - with it.
        ProcessNotifications::dispatch(
            $growthSession->id,
            $initiator->id,
            $notificationType,
            $growthSession->notifiableUserIdsExcludingInitiator($initiator),
            $growthSession->toNotificationMetadata(),
        );
    }
}
