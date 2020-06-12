<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['content'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function socialMob()
    {
        return $this->belongsTo(SocialMob::class);
    }
}
