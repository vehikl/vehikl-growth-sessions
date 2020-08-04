<?php

namespace App\Policies;

use App\SocialMob;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SocialMobPolicy
{
    use HandlesAuthorization;

    private function isInTheFuture(SocialMob $mob): bool
    {
        return today()->diffInDays($mob->date, false) >= 0;
    }

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, SocialMob $socialMob)
    {
        return true;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, SocialMob $socialMob)
    {
        return $user->is($socialMob->owner) && $this->isInTheFuture($socialMob);
    }

    public function delete(User $user, SocialMob $socialMob)
    {
        return $user->is($socialMob->owner) && $this->isInTheFuture($socialMob);
    }

    public function restore(User $user, SocialMob $socialMob)
    {
        return false;
    }

    public function forceDelete(User $user, SocialMob $socialMob)
    {
        return $user->is($socialMob->owner) && $this->isInTheFuture($socialMob);
    }
}
