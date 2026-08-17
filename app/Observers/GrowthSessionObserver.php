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

        $timeChanged = $growthSession->wasChanged(['start_time', 'end_time']);
        $locationChanged = $growthSession->wasChanged(['location']);

        // One save is one notification, even when it moved both the clock and the room.
        if ($timeChanged || $locationChanged) {
            NotificationService::dispatchNotification(
                $growthSession,
                $growthSession->owner,
                match (true) {
                    $timeChanged && $locationChanged => NotificationType::GS_TIME_AND_LOCATION_CHANGED,
                    $timeChanged => NotificationType::GS_TIME_CHANGED,
                    default => NotificationType::GS_LOCATION_CHANGED,
                }
            );
        }
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
