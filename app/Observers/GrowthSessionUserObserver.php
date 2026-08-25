<?php

namespace App\Observers;

use App\Events\GrowthSessionAttendeeChanged;
use App\Events\GrowthSessionModified;
use App\Models\GrowthSessionUser;

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
        event(new GrowthSessionAttendeeChanged($growthSessionUser->growthSession));
        broadcast(new GrowthSessionModified(
            $growthSessionUser->growth_session_id,
            $action,
            GrowthSessionModified::TYPE_MAP[$growthSessionUser->getUserType()]
            ?? GrowthSessionModified::TYPE_ATTENDEES
        ));
    }
}
