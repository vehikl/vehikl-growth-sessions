<?php

namespace App;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class SocialMob extends Model
{
    protected $with = ['owner', 'attendees'];
    protected $casts = ['owner_id' => 'int'];

    protected $fillable = [
        'topic',
        'location',
        'start_time',
        'start_date',
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
        $this->attributes['start_time'] = Carbon::parse($value)->toDateTimeString();
    }

    public function setStartDateAttribute($newDate)
    {
        $date = Carbon::parse($newDate)->toDateString();
        $time = Carbon::parse($this->attributes['start_time'])->toTimeString();
        return $this->attributes['start_time'] = Carbon::parse($date.$time)->toDateTimeString();
    }

    public function scopeWeekOf($query, CarbonImmutable $referenceDate)
    {
        $startPoint = $referenceDate->isDayOfWeek(Carbon::MONDAY)
            ? $referenceDate
            : $referenceDate->modify('Last Monday');
        $endPoint = $startPoint->addDays(5);
        return $query->whereDate('start_time', '>=', $startPoint)->whereDate('start_time', '<=', $endPoint);
    }
}
