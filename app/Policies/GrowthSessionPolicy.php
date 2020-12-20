<?php

namespace App\Policies;

use App\GrowthSession;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GrowthSessionPolicy
{
    use HandlesAuthorization;

    private function isInTheFuture(GrowthSession $mob): bool
    {
        return today()->diffInDays($mob->date, false) >= 0;
    }

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, GrowthSession $growthSession)
    {
        return true;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, GrowthSession $growthSession)
    {
        return $user->is($growthSession->owner) && $this->isInTheFuture($growthSession);
    }

    public function delete(User $user, GrowthSession $growthSession)
    {
        return $user->is($growthSession->owner) && $this->isInTheFuture($growthSession);
    }

    public function restore(User $user, GrowthSession $growthSession)
    {
        return false;
    }

    public function forceDelete(User $user, GrowthSession $growthSession)
    {
        return $user->is($growthSession->owner) && $this->isInTheFuture($growthSession);
    }

    public function join(User $user, GrowthSession $growthSession)
    {
        return ! $growthSession->hasUser($user) && $this->isInTheFuture($growthSession);
    }

    public function leave(User $user, GrowthSession $growthSession)
    {
        return $growthSession->hasUser($user) && $this->isInTheFuture($growthSession);
    }
}
