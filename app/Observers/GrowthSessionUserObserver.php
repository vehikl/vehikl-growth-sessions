<?php

namespace App\Observers;

use App\Events\GrowthSessionAttendeeChanged;
use App\Events\GrowthSessionModified;
use App\Models\GrowthSessionUser;

class GrowthSessionUserObserver
{
    public function created(GrowthSessionUser $growthSessionUser): void
    {
        event(new GrowthSessionAttendeeChanged($growthSessionUser->growthSession));
        broadcast(new GrowthSessionModified(
            $growthSessionUser->growth_session_id,
            GrowthSessionModified::ACTION_UPDATED,
            GrowthSessionModified::TYPE_MAP[$growthSessionUser->getUserType()]
            ?? GrowthSessionModified::TYPE_ATTENDEES
        ));

    }

    public function deleted(GrowthSessionUser $growthSessionUser): void
    {
        event(new GrowthSessionAttendeeChanged($growthSessionUser->growthSession));
        broadcast(new GrowthSessionModified(
            $growthSessionUser->growth_session_id,
            GrowthSessionModified::ACTION_DELETED,
            GrowthSessionModified::TYPE_MAP[$growthSessionUser->getUserType()]
            ?? GrowthSessionModified::TYPE_ATTENDEES
        ));
    }

    public function forceDeleted(GrowthSessionUser $growthSessionUser): void
    {
        $this->deleted($growthSessionUser);
    }
}
