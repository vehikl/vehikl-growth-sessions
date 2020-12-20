<?php

namespace App\Events;

use App\SocialMob as GrowthSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SocialMobDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GrowthSession $socialMob;

    public function __construct(GrowthSession $socialMob)
    {
        $this->socialMob = $socialMob;
    }
}
