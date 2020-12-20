<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GrowthSessionUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $originalGrowthSessionAttributes;
    public array $newGrowthSessionAttributes;

    public function __construct(array $originalGrowthSessionAttributes, array $newGrowthSessionAttributes)
    {
        $this->originalGrowthSessionAttributes = $originalGrowthSessionAttributes;
        $this->newGrowthSessionAttributes = $newGrowthSessionAttributes;
    }
}
