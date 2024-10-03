<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserHasNotMobbedWithView extends Model
{
    public $timestamps = false;

    protected $table = 'user_has_not_mobbed_with';

    public function mainUser(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'main_user_id');
    }

    public function hasNotMobbedWith(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'has_not_mobbed_with_id');
    }
}
