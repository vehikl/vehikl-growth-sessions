<?php

namespace App\Events;

use App\Models\GrowthSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GrowthSessionUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GrowthSession $growthSession, public array $previousAttributes, public array $dirtyKeys = [])
    {
    }
}
