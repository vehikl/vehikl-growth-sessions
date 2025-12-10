<?php

namespace App\Policies;

use App\Models\GrowthSession;
use App\Models\User;

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
            || ($user && $user->is($growthSession->owner));
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

    public function join(User $user, GrowthSession $growthSession): bool
    {
        return !$growthSession->hasAttendee($user)
            && !$growthSession->hasWatcher($user)
            && $this->isInTheFuture($growthSession);
    }

    public function watch(User $user, GrowthSession $growthSession): bool
    {
        return $growthSession->allow_watchers
            && !$growthSession->hasWatcher($user)
            && !$growthSession->hasAttendee($user)
            && $this->isInTheFuture($growthSession);
    }


    public function leave(User $user, GrowthSession $growthSession): bool
    {
        return ($growthSession->hasWatcher($user) || $growthSession->hasAttendee($user)) && $this->isInTheFuture($growthSession);
    }
}
