<?php

namespace App\Observers;

use App\Enums\NotificationType;
use App\Events\GrowthSessionDeleted;
use App\Events\GrowthSessionModified;
use App\Events\GrowthSessionUpdated;
use App\Models\GrowthSession;
use App\Services\NotificationService;

class GrowthSessionObserver
{
    // No create, since events/broadcast need both the growth session and an owner assigned

    /**
     * Handle the GrowthSession "updated" event.
     */
    public function updated(GrowthSession $growthSession): void
    {
        broadcast(new GrowthSessionModified($growthSession->id, GrowthSessionModified::ACTION_UPDATED));
        event(new GrowthSessionUpdated($growthSession));

        // One save is one notification, however much it moved. Each thing that moved adds an event
        // to the list rather than selecting a different type, so a new axis is three lines here and
        // one case on the enum - never a new combination of the existing ones.
        $eventTypes = [];

        if ($growthSession->wasChanged('date')) {
            $eventTypes[] = NotificationType::GS_DATE_CHANGED;
        }

        if ($growthSession->wasChanged(['start_time', 'end_time'])) {
            $eventTypes[] = NotificationType::GS_TIME_CHANGED;
        }

        if ($growthSession->wasChanged('location')) {
            $eventTypes[] = NotificationType::GS_LOCATION_CHANGED;
        }

        // Returning before the call rather than letting the service ignore an empty list: reading
        // ->owner runs a query and can come back null for a session with no owner pivot, and an
        // edit that moved nothing notifiable must not go looking.
        if ($eventTypes === []) {
            return;
        }

        NotificationService::dispatchNotification($growthSession, $growthSession->owner, ...$eventTypes);
    }

    /**
     * Handle the GrowthSession "deleted" event.
     */
    public function deleted(GrowthSession $growthSession): void
    {
        broadcast(new GrowthSessionModified($growthSession->id, GrowthSessionModified::ACTION_DELETED));
        event(new GrowthSessionDeleted($growthSession));
    }

    /**
     * Handle the GrowthSession "restored" event.
     */
    public function restored(GrowthSession $growthSession): void
    {
        broadcast(new GrowthSessionModified($growthSession->id, GrowthSessionModified::ACTION_RESTORED));
    }

    /**
     * Handle the GrowthSession "force deleted" event.
     */
    public function forceDeleted(GrowthSession $growthSession): void
    {
        $this->deleted($growthSession);
    }
}
