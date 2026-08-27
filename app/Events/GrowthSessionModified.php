<?php

namespace App\Events;

use App\Enums\Role;
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
    const TYPE_WAITLIST = 'waitlist';

    /**
     * Which of the roster's lists the board has to redraw for a member holding this role. Owning
     * and attending both sit in the attendee list, so both redraw it - and a row still holding no
     * role has moved the seats all the same, which is why nothing is the attendee list too.
     */
    public static function typeFor(?Role $role): string
    {
        return match ($role) {
            Role::Owner, Role::Attendee, null => self::TYPE_ATTENDEES,
            Role::Watcher => self::TYPE_WATCHERS,
            Role::Waitlisted => self::TYPE_WAITLIST,
        };
    }

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
