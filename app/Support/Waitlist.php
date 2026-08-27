<?php

namespace App\Support;

use App\Models\GrowthSession;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The queue of members waiting for a seat at a full growth session, read.
 *
 * Nothing comes off the queue through here: seating is {@see Seating}'s alone, so the order the
 * queue is served in cannot be decided in two places. This is what asks where somebody stands.
 */
class Waitlist
{
    private function __construct(private readonly GrowthSession $growthSession) {}

    public static function for(GrowthSession $growthSession): self
    {
        return new self($growthSession);
    }

    /** @return Collection<int, User> Front of the queue first. */
    public function members(): Collection
    {
        return $this->growthSession->waitlist;
    }

    /** Where the member stands, counting from the front at 1, or null if they are not in line. */
    public function positionOf(?User $user): ?int
    {
        if (! $user) {
            return null;
        }

        $place = $this->members()->search(fn (User $member) => $member->is($user));

        return $place === false ? null : $place + 1;
    }
}
