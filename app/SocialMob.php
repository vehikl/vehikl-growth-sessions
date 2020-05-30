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
        'end_time',
        'date',
        'owner_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($socialMob) {
            if (empty($socialMob->end_time)) {
                $defaultEndTime = Carbon::parse($socialMob->start_time)->setTime(
                    config('socialmob.default_end_hour'), 0
                );
                $socialMob->end_time = $defaultEndTime;
            }
        });
    }

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

    public function setEndTimeAttribute($value)
    {
        $this->attributes['end_time'] = Carbon::parse($value)->toDateTimeString();
    }

    public function setDateAttribute($newDate)
    {
        $date = Carbon::parse($newDate)->toDateString();
        $startHour = Carbon::parse($this->attributes['start_time'])->toTimeString();
        $endHour = Carbon::parse($this->attributes['end_time'])->toTimeString();
        $this->attributes['start_time'] = Carbon::parse($date . $startHour)->toDateTimeString();
        $this->attributes['end_time'] = Carbon::parse($date . $endHour)->toDateTimeString();
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
