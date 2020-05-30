<?php

namespace App;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class SocialMob extends Model
{
    protected $with = ['owner', 'attendees'];

    protected $casts = [
        'owner_id' => 'int',
        'start_time' => 'time:h:i a',
        'end_time' => 'time:h:i a',
        'date' => 'date:Y-m-d',
    ];

    protected $fillable = [
        'topic',
        'location',
        'start_time',
        'end_time',
        'date',
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

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = Carbon::parse($value)->format('Y-m-d');
    }

    public function setStartTimeAttribute($value)
    {
        $this->attributes['start_time'] = Carbon::parse($value)->format('h:i a');
    }

    public function setEndTimeAttribute($value)
    {
        $this->attributes['end_time'] = Carbon::parse($value)->format('h:i a');
    }

    public function scopeWeekOf($query, CarbonImmutable $referenceDate)
    {
        $startPoint = $referenceDate->isDayOfWeek(Carbon::MONDAY)
            ? $referenceDate
            : $referenceDate->modify('Last Monday');
        $endPoint = $startPoint->addDays(4);
        return $query->whereDate('date', '>=', $startPoint)->whereDate('date', '<=', $endPoint);
    }
}
