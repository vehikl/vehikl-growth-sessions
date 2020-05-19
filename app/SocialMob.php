<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SocialMob extends Model
{
    protected $fillable = [
        'topic',
        'start_time',
        'owner_id'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
