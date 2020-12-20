<?php

namespace App\Events;

use App\SocialMob as GrowthSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SocialMobAttendeeChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GrowthSession $growthSession;

    public function __construct(GrowthSession $growthSession)
    {
        $this->growthSession = $growthSession;
    }
}
