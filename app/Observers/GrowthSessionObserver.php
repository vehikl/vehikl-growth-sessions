<?php

namespace App\Observers;

use App\Events\GrowthSessionDeleted;
use App\Events\GrowthSessionModified;
use App\Models\GrowthSession;

class GrowthSessionObserver
{
    /**
     * Handle the GrowthSession "created" event.
     */
    public function created(GrowthSession $growthSession): void
    {
        broadcast(new GrowthSessionModified($growthSession, GrowthSessionModified::ACTION_CREATED));
    }

    /**
     * Handle the GrowthSession "updated" event.
     */
    public function updated(GrowthSession $growthSession): void
    {
        broadcast(new GrowthSessionModified($growthSession, GrowthSessionModified::ACTION_UPDATED));
    }

    /**
     * Handle the GrowthSession "deleted" event.
     */
    public function deleted(GrowthSession $growthSession): void
    {
        broadcast(new GrowthSessionDeleted($growthSession));
    }

    /**
     * Handle the GrowthSession "restored" event.
     */
    public function restored(GrowthSession $growthSession): void
    {
        broadcast(new GrowthSessionModified($growthSession, GrowthSessionModified::ACTION_RESTORED));
    }

    /**
     * Handle the GrowthSession "force deleted" event.
     */
    public function forceDeleted(GrowthSession $growthSession): void
    {
        broadcast(new GrowthSessionDeleted($growthSession));
    }
}
