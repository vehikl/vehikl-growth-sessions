<?php

namespace App\Observers;

use App\Events\GrowthSessionDeleted;
use App\Events\GrowthSessionModified;
use App\Events\GrowthSessionUpdated;
use App\Models\GrowthSession;
use App\Support\SeriesAssignment;

class GrowthSessionObserver
{
    // No create, since events/broadcast need both the growth session and an owner assigned

    /**
     * Handle the GrowthSession "updated" event.
     *
     * A series lives for exactly as long as it holds a session, so a session moving out of one is
     * how a thread comes to an end. The move can only be seen from the row, which is why it is
     * noticed here rather than by whoever filed it.
     *
     * Which thread it left is read off the original, so this has to happen before the event goes
     * out: a listener is free to `refresh()` the session it is handed - the Slack poster does -
     * and that re-syncs the original to the row it was just saved as, leaving nothing to say what
     * the session moved out of.
     */
    public function updated(GrowthSession $growthSession): void
    {
        if ($growthSession->wasChanged('series_id')) {
            SeriesAssignment::prune($growthSession->getOriginal('series_id'));
        }

        broadcast(new GrowthSessionModified($growthSession->id, GrowthSessionModified::ACTION_UPDATED));
        event(new GrowthSessionUpdated($growthSession));
    }

    /**
     * Handle the GrowthSession "deleting" event.
     *
     * Deletes are hard deletes, so anything downstream that needs to know who was attending
     * (e.g. the deletion notification) has to be loaded before the row — and its pivot rows —
     * disappear. Loaded here, it stays cached on this same instance through to `deleted()`.
     */
    public function deleting(GrowthSession $growthSession): void
    {
        $growthSession->loadMissing(['attendees', 'watchers', 'owners']);
    }

    /**
     * Handle the GrowthSession "deleted" event.
     */
    public function deleted(GrowthSession $growthSession): void
    {
        SeriesAssignment::prune($growthSession->series_id);

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
