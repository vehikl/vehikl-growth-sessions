<?php

namespace App\Observers;

use App\Events\SessionCreated;
use App\Models\GrowthSession;

class GrowthSessionObserver
{
    /**
     * Handle the GrowthSession "created" event.
     */
    public function created(GrowthSession $growthSession): void
    {
        broadcast(new SessionCreated($growthSession));
    }

    /**
     * Handle the GrowthSession "updated" event.
     */
    public function updated(GrowthSession $growthSession): void
    {
        //
    }

    /**
     * Handle the GrowthSession "deleted" event.
     */
    public function deleted(GrowthSession $growthSession): void
    {
        //
    }

    /**
     * Handle the GrowthSession "restored" event.
     */
    public function restored(GrowthSession $growthSession): void
    {
        //
    }

    /**
     * Handle the GrowthSession "force deleted" event.
     */
    public function forceDeleted(GrowthSession $growthSession): void
    {
        //
    }
}
