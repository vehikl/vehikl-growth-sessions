<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SocialMob extends Model
{
    protected $with = ['owner', 'attendees'];
    protected $casts = ['owner_id' => 'int'];

    protected $fillable = [
        'topic',
        'location',
        'start_time',
        'owner_id'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function attendees()
    {
        return $this->belongsToMany(User::class);
    }

    public function dayOfTheWeek(): string
    {
        return Str::lower(Carbon::parse($this->start_time)->englishDayOfWeek);
    }

    public function scopeThisWeek($query)
    {
        $MONDAY = 1;
        $FRIDAY = 5;
        $startPoint = now()->isDayOfWeek($MONDAY) ? Carbon::today() : Carbon::parse('Last Monday');
        $endPoint = now()->isDayOfWeek($FRIDAY) ? Carbon::today() : Carbon::parse('Next Saturday');
        return $query->where('start_time', '>=', $startPoint)->where('start_time', '<=', $endPoint);
    }
}
