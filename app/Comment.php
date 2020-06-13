<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['content'];
    protected $with = ['user'];
    protected $appends = ['time_stamp'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function socialMob()
    {
        return $this->belongsTo(SocialMob::class);
    }

    public function getTimeStampAttribute()
    {
        return $this->created_at->diffForHumans();
    }
}
