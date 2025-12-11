<?php

namespace App\Events;

use App\Models\GrowthSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GrowthSessionModified implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_RESTORED = 'restored';

    public function __construct(public GrowthSession $growthSession, public string $action)
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
