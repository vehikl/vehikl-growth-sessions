<?php

namespace App\Policies;

use App\Models\GrowthSession;
use App\Models\User;
use App\Support\InviteLink;
use Illuminate\Auth\Access\Response;

class GrowthSessionPolicy
{
    private function isInTheFuture(GrowthSession $growthSession): bool
    {
        return today()->diffInDays($growthSession->date, false) >= 0;
    }

    /**
     * Every question here is answered from the same relations, and route model binding hands the
     * policy a bare model - so they are loaded once, here, rather than by whichever caller first
     * trips over lazy loading. A session that arrived eager-loaded pays nothing.
     */
    private function rolesOf(GrowthSession $growthSession): GrowthSession
    {
        return $growthSession->loadMissing(GrowthSession::VISIBILITY_RELATIONS);
    }

    /**
     * Whether the member already holds a role - a seat, a spectator's place, or a place in line.
     * Holding one is what rules out taking a second, so join, watch and leave all ask it here
     * rather than restating it for themselves.
     */
    private function alreadyTakesPart(User $user, GrowthSession $growthSession): bool
    {
        return $this->rolesOf($growthSession)->roleOf($user) !== null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(?User $user, GrowthSession $growthSession): bool
    {
        return $growthSession->is_public
            || optional($user)->is_vehikl_member
            || ($user && $user->is($growthSession->owner))
            // Taking part is a deliberate act by an authenticated identity, so it grants visibility on its own — it
            // does not depend on still holding the invite token or on the browser session that unlocked it.
            || ($user && $this->alreadyTakesPart($user, $growthSession))
            || InviteLink::for($growthSession)->hasBeenUnlocked();
    }

    public function create(User $user): bool
    {
        return $user->is_vehikl_member;
    }

    public function viewAnyDesks(User $user): bool
    {
        return $user->is_vehikl_member;
    }

    public function update(User $user, GrowthSession $growthSession): bool
    {
        return $user->is($growthSession->owner) && $this->isInTheFuture($growthSession);
    }

    /**
     * Unlike `update`, this stays open on past sessions: an owner groups what they have already
     * run from the dashboard.
     */
    public function fileInSeries(User $user, GrowthSession $growthSession): bool
    {
        return $user->is($growthSession->owner);
    }

    public function delete(User $user, GrowthSession $growthSession): bool
    {
        return $user->is($growthSession->owner) && $this->isInTheFuture($growthSession);
    }

    public function restore(User $user, GrowthSession $growthSession): bool
    {
        return false;
    }

    public function forceDelete(User $user, GrowthSession $growthSession): bool
    {
        return $user->is($growthSession->owner) && $this->isInTheFuture($growthSession);
    }

    public function join(User $user, GrowthSession $growthSession): bool|Response
    {
        // Someone who cannot see the growth session must not learn that it exists by trying to join it: the join
        // response hands back its contents, and attendance would then grant them lasting visibility.
        if (! $this->view($user, $growthSession)) {
            return Response::denyAsNotFound();
        }

        return ! $this->alreadyTakesPart($user, $growthSession)
            && $this->isInTheFuture($growthSession);
    }

    /**
     * A place in line is a role of its own, so somebody already queueing is refused here - taking up
     * a spectator's place means giving the place up first, and a promotion never lands on somebody
     * who has moved on.
     */
    public function watch(User $user, GrowthSession $growthSession): bool
    {
        return $growthSession->allow_watchers
            && ! $this->alreadyTakesPart($user, $growthSession)
            && $this->isInTheFuture($growthSession);
    }

    public function leave(User $user, GrowthSession $growthSession): bool
    {
        return ! $user->is($growthSession->owner)
            && $this->alreadyTakesPart($user, $growthSession)
            && $this->isInTheFuture($growthSession);
    }
}
