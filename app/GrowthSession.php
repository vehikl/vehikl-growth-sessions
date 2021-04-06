<?php

namespace App;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrowthSession extends Model
{
    use HasFactory;

    const NO_LIMIT = PHP_INT_MAX;
    protected $with = ['owner', 'attendees', 'comments'];

    protected $casts = [
        'owner_id' => 'int',
        'start_time' => 'datetime:h:i a',
        'end_time' => 'datetime:h:i a',
        'date' => 'datetime:Y-m-d',
        'attendee_limit' => 'int',
        'is_vehikl_only' => 'bool'
    ];

    protected $fillable = [
        'title',
        'topic',
        'location',
        'start_time',
        'end_time',
        'date',
        'owner_id',
        'attendee_limit',
        'discord_channel_id',
        'is_vehikl_only',
    ];

    protected $attributes = [
        'end_time' => '17:00',
        'attendee_limit' => self::NO_LIMIT,
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function attendees()
    {
        return $this->belongsToMany(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->orderByDesc('created_at');
    }

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = Carbon::parse($value)->format('Y-m-d');
    }

    public function setStartTimeAttribute($value)
    {
        $this->attributes['start_time'] = Carbon::parse($value)->format('H:i');
    }

    public function setEndTimeAttribute($value)
    {
        $this->attributes['end_time'] = Carbon::parse($value)->format('H:i');
    }

    public function getIsPubliclyAvailableAttribute(): bool
    {
        return ! $this->is_vehikl_only;
    }

    public static function allInTheWeekOf(?string $referenceDate)
    {
        $referenceDate = CarbonImmutable::parse($referenceDate);

        $startPoint = $referenceDate->isDayOfWeek(Carbon::MONDAY)
            ? $referenceDate
            : $referenceDate->modify('Last Monday');
        $endPoint = $startPoint->addDays(4);

        $allWeekGrowthSessions = GrowthSession::query()
            ->whereDate('date', '>=', $startPoint)
            ->whereDate('date', '<=', $endPoint)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return $allWeekGrowthSessions;
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', today()->toDateString());
    }

    public function hasUser(User $user): bool
    {
        return ! ! $this->attendees->find($user);
    }
}
