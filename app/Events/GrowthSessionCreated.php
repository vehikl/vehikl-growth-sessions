<?php

namespace App\Events;

use App\SocialMob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GrowthSessionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SocialMob $growthSession;

    public function __construct(SocialMob $growthSession)
    {
        $this->growthSession = $growthSession;
    }
}
