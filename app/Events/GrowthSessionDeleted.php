<?php

namespace App\Events;

use App\SocialMob as GrowthSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GrowthSessionDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GrowthSession $socialMob;

    public function __construct(GrowthSession $growthSession)
    {
        $this->socialMob = $growthSession;
    }
}
