<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

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

    public function setStartTimeAttribute($value)
    {
        $this->attributes['start_time'] = Carbon::parse($value)->toIso8601ZuluString();
    }

    public function scopeThisWeek($query)
    {
        $startPoint = now()->isDayOfWeek(Carbon::MONDAY) ? Carbon::today() : Carbon::parse('Last Monday');
        $endPoint = $startPoint->toImmutable()->addDays(5);
        return $query->whereDate('start_time', '>=', $startPoint)->whereDate('start_time', '<=', $endPoint);
    }
}
