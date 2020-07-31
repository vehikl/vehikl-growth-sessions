<?php

namespace App\Policies;

use App\SocialMob;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SocialMobPolicy
{
    use HandlesAuthorization;

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
        return $user->is($socialMob->owner);
    }

    public function delete(User $user, SocialMob $socialMob)
    {
        return $user->is($socialMob->owner);
    }

    public function restore(User $user, SocialMob $socialMob)
    {
        return false;
    }

    public function forceDelete(User $user, SocialMob $socialMob)
    {
        return $user->is($socialMob->owner);
    }
}
