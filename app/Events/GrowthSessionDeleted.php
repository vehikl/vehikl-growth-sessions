<?php

namespace App\Events;

use App\Models\GrowthSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class GrowthSessionDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    const ACTION_DELETED = 'deleted';

    public function __construct(public GrowthSession $growthSession, public string $action = self::ACTION_DELETED)
    {
        //
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('gs-channel')
        ];
    }

    public static function broadcastAs()
    {
        return 'session.modified';
    }
}
