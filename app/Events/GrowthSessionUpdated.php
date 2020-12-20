<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GrowthSessionUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $originalSocialMobAttributes;
    public array $newSocialMobAttributes;

    public function __construct(array $originalSocialMobAttributes, array $newSocialMobAttributes)
    {
        $this->originalSocialMobAttributes = $originalSocialMobAttributes;
        $this->newSocialMobAttributes = $newSocialMobAttributes;
    }
}
