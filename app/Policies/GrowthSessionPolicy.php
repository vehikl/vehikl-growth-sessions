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

    public function view(User $user, GrowthSession $socialMob)
    {
        return true;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, GrowthSession $socialMob)
    {
        return $user->is($socialMob->owner) && $this->isInTheFuture($socialMob);
    }

    public function delete(User $user, GrowthSession $socialMob)
    {
        return $user->is($socialMob->owner) && $this->isInTheFuture($socialMob);
    }

    public function restore(User $user, GrowthSession $socialMob)
    {
        return false;
    }

    public function forceDelete(User $user, GrowthSession $socialMob)
    {
        return $user->is($socialMob->owner) && $this->isInTheFuture($socialMob);
    }

    public function join(User $user, GrowthSession $socialMob)
    {
        return ! $socialMob->hasUser($user) && $this->isInTheFuture($socialMob);
    }

    public function leave(User $user, GrowthSession $socialMob)
    {
        return $socialMob->hasUser($user) && $this->isInTheFuture($socialMob);
    }
}
