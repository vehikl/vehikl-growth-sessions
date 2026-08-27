<?php

namespace App\Observers;

use App\Events\GrowthSessionModified;
use App\Models\GrowthSessionUser;

/**
 * Redraws the board for whoever is looking at it when a roster row changes - and nothing else.
 *
 * Every row matters here: spectators and the queue are drawn on the board too, and the broadcast is
 * a push to an already-open socket, so saying it once per row costs a message rather than a request
 * to somebody else's service. What does cost such a request - posting the attendees to Slack - is
 * decided by {@see \App\Support\Seating} instead, which is the only thing that knows whether the
 * seats changed hands at all and is the only place that can wait for the commit before saying so.
 */
class GrowthSessionUserObserver
{
    public function created(GrowthSessionUser $growthSessionUser): void
    {
        $this->announce($growthSessionUser, GrowthSessionModified::ACTION_UPDATED);
    }

    /**
     * A promotion off the waitlist rewrites the role on a row that already exists, so without this
     * the board would keep showing the seat as taken by nobody until somebody reloaded it.
     */
    public function updated(GrowthSessionUser $growthSessionUser): void
    {
        $this->announce($growthSessionUser, GrowthSessionModified::ACTION_UPDATED);
    }

    public function deleted(GrowthSessionUser $growthSessionUser): void
    {
        $this->announce($growthSessionUser, GrowthSessionModified::ACTION_DELETED);
    }

    public function forceDeleted(GrowthSessionUser $growthSessionUser): void
    {
        $this->deleted($growthSessionUser);
    }

    private function announce(GrowthSessionUser $growthSessionUser, string $action): void
    {
        broadcast(new GrowthSessionModified(
            $growthSessionUser->growth_session_id,
            $action,
            GrowthSessionModified::typeFor($growthSessionUser->user_type_id)
        ));
    }
}
