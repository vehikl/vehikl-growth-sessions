<?php

namespace App\Policies;

use App\Models\GrowthSession;
use App\Models\User;
use App\Support\GrowthSessionUnlocks;
use Illuminate\Auth\Access\Response;

class GrowthSessionPolicy
{
    private function isInTheFuture(GrowthSession $growthSession): bool
    {
        return today()->diffInDays($growthSession->date, false) >= 0;
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
            || ($user && $growthSession->hasParticipant($user))
            || $this->hasUnlockedInviteLink($growthSession);
    }

    private function hasUnlockedInviteLink(GrowthSession $growthSession): bool
    {
        return $growthSession->isUnlisted()
            && GrowthSessionUnlocks::has($growthSession->share_token);
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

        return !$growthSession->hasParticipant($user)
            && $this->isInTheFuture($growthSession);
    }

    public function watch(User $user, GrowthSession $growthSession): bool
    {
        return $growthSession->allow_watchers
            && !$growthSession->hasParticipant($user)
            && $this->isInTheFuture($growthSession);
    }


    public function leave(User $user, GrowthSession $growthSession): bool
    {
        return $growthSession->hasParticipant($user) && $this->isInTheFuture($growthSession);
    }
}
