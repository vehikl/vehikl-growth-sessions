<?php

namespace App\Observers;

use App\Events\GrowthSessionCreated;
use App\Events\GrowthSessionDeleted;
use App\Events\GrowthSessionModified;
use App\Events\GrowthSessionUpdated;
use App\Models\GrowthSession;

class GrowthSessionObserver
{
    // No create, since events/broadcast need both the growth session and an owner assigned

    /**
     * Handle the GrowthSession "updated" event.
     */
    public function updating(GrowthSession $growthSession): bool
    {
        broadcast(new GrowthSessionModified($growthSession->id, GrowthSessionModified::ACTION_UPDATED));
        event(new GrowthSessionUpdated($growthSession, $growthSession->getOriginal(), $growthSession->getDirty()));
        return true;
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
