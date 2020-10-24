<?php

namespace App\Events;

use App\SocialMob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SocialMobDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SocialMob $socialMob;

    public function __construct(SocialMob $socialMob)
    {
        $this->socialMob = $socialMob;
    }
}
