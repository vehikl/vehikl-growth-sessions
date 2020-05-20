<?php

namespace App;

use Carbon\Carbon;
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

    public function scopeThisWeek($query)
    {
        $MONDAY = 1;
        $SATURDAY = 5;
        $startDay = now()->isDayOfWeek($MONDAY) ? Carbon::today() : Carbon::parse('Last Monday');
        $endDay = now()->isDayOfWeek($SATURDAY) ? Carbon::today() : Carbon::parse('This Saturday');
        return $query->where('start_time', '>=', $startDay)->where('start_time', '<=', $endDay);
    }
}
