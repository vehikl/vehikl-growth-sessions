<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\GrowthSession;
use App\Models\GrowthSessionUser;
use App\Models\User;
use App\Notifications\PromotedFromTheWaitlistNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Who holds what at a growth session, and the only thing that writes it.
 *
 * Taking a seat, taking a spectator's place, giving either up, and serving the queue after the
 * limit was raised all run through here, under one lock on the growth session - so "how many seats
 * are taken" is read once, under that lock, on every path that acts on it. Deciding outside would
 * be deciding on a count that may already be stale by the time the write lands.
 *
 * Callers say what should happen to the roster, not how it is stored: none of them names a user
 * type, and none of them chooses between a seat and a place in line.
 */
class Seating
{
    private function __construct(private readonly GrowthSession $growthSession) {}

    public static function for(GrowthSession $growthSession): self
    {
        return new self($growthSession);
    }

    /**
     * Take a seat if one is free, and a place at the back of the queue if none is.
     *
     * Asking again from a place already held changes nothing: the moment they asked is what orders
     * the queue, so rewriting it would send them to the back for having asked twice.
     */
    public function take(User $user): void
    {
        $this->serve(function (int $seatsFree) use ($user) {
            if ($this->roleOf($user) === Role::Waitlisted) {
                return;
            }

            $seatsFree > 0
                ? $this->write($user, Role::Attendee)
                : $this->write($user, Role::Waitlisted, now());
        });
    }

    /**
     * Take a spectator's place. Spectating costs no seat, so it never displaces the queue - but it
     * is written under the same lock, so it cannot race a promotion rewriting the same row.
     */
    public function spectate(User $user): void
    {
        $this->serve(fn () => $this->write($user, Role::Watcher));
    }

    /**
     * Give up whatever place is held here - a seat, a spectator's place, or a place in line - and
     * serve the queue in the same breath.
     *
     * Freeing the seat and filling it are one write: committing the departure on its own would
     * leave a growth session sitting full-but-empty beside a queue nobody can serve, and nothing in
     * the interface can recover that state, since being in line is what disqualifies the member at
     * the front from simply asking again.
     */
    public function release(User $user): void
    {
        $this->serve(fn () => $this->rows()->where('user_id', $user->id)->delete());
    }

    /**
     * Hand every free seat to the front of the queue, in order, and tell whoever got one. This is
     * the path for a seat coming free some other way - the owner raising the attendee limit.
     *
     * Idempotent, and safe to call on any growth session: one with seats to spare has nobody
     * queueing, and one whose limit has just been lowered has no free seats - so neither promotes
     * anybody, and nobody is ever demoted. A growth session whose day has passed is left alone.
     */
    public function reseat(): void
    {
        $this->serve(fn () => null);
    }

    /**
     * The one lock every path passes through, and the one place the day is checked - so no caller
     * can free a seat at a growth session that is over and then seat somebody into it.
     *
     * Telling the promoted member waits for the commit: the seat is already theirs before anyone
     * tries to reach them, and a delivery channel that will not answer must not cost them it.
     *
     * @param  callable(int): void  $write  Handed the seats free before it runs.
     */
    private function serve(callable $write): void
    {
        $promoted = DB::transaction(function () use ($write) {
            // Locking the growth session itself rather than its pivot rows: it is the one row that
            // is always there, so two members asking for the last seat at a growth session nobody
            // has joined yet still queue up behind each other here.
            $limit = (int) GrowthSession::query()
                ->whereKey($this->growthSession->id)
                ->lockForUpdate()
                ->value('attendee_limit');

            if ($this->hasPassed()) {
                $write(0);

                return collect();
            }

            $write(max(0, $limit - $this->seatsTaken()));

            return $this->seatWhoeverFits($limit);
        });

        $this->forgetRoster();
        $promoted->each(fn (User $user) => $this->announce($user));
    }

    /** @return Collection<int, User> */
    private function seatWhoeverFits(int $limit): Collection
    {
        // Both relations are read while the promotion runs - the member so they can be told, and the
        // growth session by the pivot observer that keeps the board current - so they are loaded up
        // front rather than one row at a time. Outside production that is not merely an N+1: lazy
        // loading is prevented there, so reaching for either would throw and roll the seating back.
        $queue = $this->rows()
            ->with(['user', 'growthSession'])
            ->where('user_type_id', Role::Waitlisted->value)
            ->orderBy('waitlisted_at')
            ->orderBy('user_id')
            ->take(max(0, $limit - $this->seatsTaken()))
            ->get();

        return $queue->map(function (GrowthSessionUser $enrolment) {
            $enrolment->user_type_id = Role::Attendee;
            $enrolment->waitlisted_at = null;
            $enrolment->save();

            return $enrolment->user;
        })->values();
    }

    private function write(User $user, Role $role, $waitlistedAt = null): void
    {
        $enrolment = GrowthSessionUser::query()->firstOrNew([
            'growth_session_id' => $this->growthSession->id,
            'user_id' => $user->id,
        ]);

        $enrolment->user_type_id = $role;
        $enrolment->waitlisted_at = $waitlistedAt;
        // The pivot observer reads the growth session off the row it was handed, so it is given the
        // one already in hand rather than left to fetch it back out of the database.
        $enrolment->setRelation('growthSession', $this->growthSession);
        $enrolment->save();
    }

    /** What the member holds right now, read from the row itself rather than a roster that predates the lock. */
    private function roleOf(User $user): ?Role
    {
        return $this->rows()->where('user_id', $user->id)->value('user_type_id');
    }

    private function seatsTaken(): int
    {
        return $this->rows()
            ->whereIn('user_type_id', Role::seatOccupyingIds())
            ->count();
    }

    private function rows()
    {
        return GrowthSessionUser::query()->where('growth_session_id', $this->growthSession->id);
    }

    /** The roster held in memory predates the seating, so it is dropped rather than left to mislead. */
    private function forgetRoster(): void
    {
        $this->growthSession->unsetRelation('waitlist');
        $this->growthSession->unsetRelation('attendees');
        $this->growthSession->unsetRelation('watchers');
    }

    private function announce(User $user): void
    {
        try {
            $user->notify(new PromotedFromTheWaitlistNotification($this->growthSession));
        } catch (Throwable $failure) {
            report($failure);
        }
    }

    private function hasPassed(): bool
    {
        return $this->growthSession->date->lt(today());
    }
}
