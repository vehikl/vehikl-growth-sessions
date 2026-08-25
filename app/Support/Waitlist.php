<?php

namespace App\Support;

use App\Models\GrowthSession;
use App\Models\GrowthSessionUser;
use App\Models\User;
use App\Models\UserType;
use App\Notifications\PromotedFromTheWaitlistNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The queue of members waiting for a seat at a full growth session, and the only way anybody comes
 * off it.
 *
 * Every path that frees a seat - an attendee leaving through {@see withdraw()}, an owner raising
 * the limit through {@see promote()} - hands the seating over here rather than deciding for itself
 * who is next, so the queue can only ever be served in the order it was joined. Promotion is
 * immediate: there is no window in which a seat is held open for someone to claim.
 *
 * Callers say what should happen to the queue, not how the roster is stored; enrolment and
 * promotion write through the pivot so the observers that keep the board current still fire.
 */
class Waitlist
{
    private function __construct(private readonly GrowthSession $growthSession) {}

    public static function for(GrowthSession $growthSession): self
    {
        return new self($growthSession);
    }

    /**
     * Take a place at the back of the queue. Asking again from a place already held changes
     * nothing: the moment they asked is what orders the queue, so rewriting it would send them to
     * the back for having asked twice.
     */
    public function enrol(User $user): void
    {
        $enrolment = GrowthSessionUser::query()->firstOrNew([
            'growth_session_id' => $this->growthSession->id,
            'user_id' => $user->id,
        ]);

        if ($enrolment->user_type_id === UserType::WAITLISTED_ID) {
            return;
        }

        $enrolment->user_type_id = UserType::WAITLISTED_ID;
        $enrolment->waitlisted_at = now();
        // The pivot observer reads the growth session off the row it was handed, so it is given the
        // one already in hand rather than left to fetch it back out of the database.
        $enrolment->setRelation('growthSession', $this->growthSession);
        $enrolment->save();

        $this->growthSession->unsetRelation('waitlist');
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

    /**
     * Hand every free seat to the front of the queue, in order, and tell whoever got one.
     *
     * Idempotent, and safe to call on any growth session: one with seats to spare has nobody
     * queueing, and one whose limit has just been lowered has no free seats - so neither promotes
     * anybody, and nobody is ever demoted. A growth session whose day has passed is left alone.
     */
    public function promote(): void
    {
        // Seating runs under a lock on the growth session's pivot rows so two people leaving at the
        // same moment cannot each hand the same seat to a different member of the queue. Telling
        // people waits for the commit - the seat is theirs before anyone tries to reach them.
        $promoted = DB::transaction(fn () => $this->seatWhoeverFits());

        $this->forgetRoster();
        $this->announceTo($promoted);
    }

    /**
     * Give up whatever place is held here - a seat or a place in line - and serve the queue in the
     * same breath.
     *
     * Freeing the seat and filling it are one write: committing the departure on its own would
     * leave a growth session sitting full-but-empty beside a queue nobody can serve, and nothing in
     * the interface can recover that state, since being in line is what disqualifies the member at
     * the front from simply joining.
     */
    public function withdraw(User $user): void
    {
        $promoted = DB::transaction(function () use ($user) {
            GrowthSessionUser::query()
                ->where('growth_session_id', $this->growthSession->id)
                ->where('user_id', $user->id)
                ->delete();

            return $this->seatWhoeverFits();
        });

        $this->forgetRoster();
        $this->announceTo($promoted);
    }

    /** The roster held in memory predates the seating, so it is dropped rather than left to mislead. */
    private function forgetRoster(): void
    {
        $this->growthSession->unsetRelation('waitlist');
        $this->growthSession->unsetRelation('attendees');
    }

    /** @param  Collection<int, User>  $promoted */
    private function announceTo(Collection $promoted): void
    {
        $promoted->each(fn (User $user) => $this->announce($user));
    }

    /** @return Collection<int, User> */
    private function seatWhoeverFits(): Collection
    {
        // The one place the day is checked, so no caller can free a seat at a growth session that
        // is over and then promote somebody into it.
        if ($this->hasPassed()) {
            return collect();
        }

        // Both relations are read while the promotion runs - the member so they can be told, and the
        // growth session by the pivot observer that keeps the board current - so they are loaded up
        // front rather than one row at a time. Outside production that is not merely an N+1: lazy
        // loading is prevented there, so reaching for either would throw and roll the seating back.
        $roster = GrowthSessionUser::query()
            ->with(['user', 'growthSession'])
            ->where('growth_session_id', $this->growthSession->id)
            ->lockForUpdate()
            ->orderBy('waitlisted_at')
            ->orderBy('user_id')
            ->get();

        // Read under the lock rather than trusting the copy in memory, which may predate the very
        // change that freed the seats.
        $limit = (int) GrowthSession::query()->whereKey($this->growthSession->id)->value('attendee_limit');
        $seatsTaken = $roster->whereIn('user_type_id', [UserType::OWNER_ID, UserType::ATTENDEE_ID])->count();

        return $roster
            ->where('user_type_id', UserType::WAITLISTED_ID)
            ->take(max(0, $limit - $seatsTaken))
            ->map(function (GrowthSessionUser $enrolment) {
                $enrolment->user_type_id = UserType::ATTENDEE_ID;
                $enrolment->waitlisted_at = null;
                $enrolment->save();

                return $enrolment->user;
            })
            ->values();
    }

    /**
     * Telling the member is a courtesy, not part of the promotion. A delivery channel that is not
     * configured, or a broker that will not answer, must not cost them the seat they hold.
     */
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
