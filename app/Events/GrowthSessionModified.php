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
    const ACTION_DELETED = 'deleted';

    const TYPE_SESSION = 'session';
    const TYPE_COMMENT = 'comment';
    const TYPE_ATTENDEES = 'attendees';
    const TYPE_WATCHERS = 'watchers';

    const TYPE_MAP = [
        'owner' => self::TYPE_ATTENDEES,
        'attendee' => self::TYPE_ATTENDEES,
        'watcher' => self::TYPE_WATCHERS,
    ];

    public static function fire(GrowthSession $growthSession, string $action, string $type): self
    {
        $event = new self($growthSession->id, $action, $type);
        event($event);
        broadcast($event);
        return $event;
    }

    public function __construct(public int $growthSessionId, public string $action, public string $type = self::TYPE_SESSION)
    {
        //
    }

    public function growthSession(): ?GrowthSession
    {
        return GrowthSession::find($this->growthSessionId);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('gs-channel'),
            new Channel(sprintf('gs-channel.%s', $this->growthSessionId)),
        ];
    }

    public static function broadcastAs()
    {
        return 'session.modified';
    }
}
