<?php

namespace App\Events;

use App\Models\GrowthSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GrowthSession $growthSession)
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
        return 'session.created';
    }
}
