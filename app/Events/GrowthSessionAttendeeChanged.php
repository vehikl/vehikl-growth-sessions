<?php

namespace App\Events;

use App\Models\GrowthSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The seats at a growth session changed hands: somebody took one, gave one up, or was moved off the
 * queue into one. Not spectators, and not the queue itself - both leave the seats exactly as they
 * were, and everything downstream of this reports on the seats.
 *
 * Said once per change, by {@see \App\Support\Seating} - the only thing that writes a roster, and so
 * the only thing that can tell a seat changing hands from a spectator arriving.
 *
 * Held until the change commits, because what listens to this posts to Slack: raised inside a
 * transaction it would keep the growth session locked for as long as Slack took to answer, and a
 * Slack that answered with an error would roll back a seat that had already been handed out. Stated
 * as a contract rather than by dispatching in the right place, so it holds however deep inside a
 * caller's own transaction the seating happens to be nested.
 */
class GrowthSessionAttendeeChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GrowthSession $growthSession;

    public function __construct(GrowthSession $growthSession)
    {
        $this->growthSession = $growthSession;
    }
}
