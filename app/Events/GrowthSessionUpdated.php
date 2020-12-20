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

    public function __construct(array $originalGrowthSessionAttributes, array $newGrowthSessionAttributes)
    {
        $this->originalSocialMobAttributes = $originalGrowthSessionAttributes;
        $this->newSocialMobAttributes = $newGrowthSessionAttributes;
    }
}
