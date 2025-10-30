<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GrowthSession extends Model
{
    use HasFactory;

    const NO_LIMIT = PHP_INT_MAX;

    protected $appends = ['owner'];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:h:i a',
            'end_time' => 'datetime:h:i a',
            'date' => 'datetime:Y-m-d',
            'attendee_limit' => 'int',
            'is_public' => 'bool',
            'allow_watchers' => 'bool',
        ];
    }

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
        'is_public',
        'anydesk_id',
        'allow_watchers'
    ];

    protected $attributes = [
        'end_time' => '17:00',
        'attendee_limit' => self::NO_LIMIT,
    ];

    protected function owner(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->owners()->first(),
        );
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function owners()
    {
        return $this->belongsToMany(User::class)->wherePivot('user_type_id', UserType::OWNER_ID);
    }

    public function attendees()
    {
        return $this->belongsToMany(User::class)
            ->wherePivotIn('user_type_id', [UserType::ATTENDEE_ID, UserType::OWNER_ID]);
    }

    public function watchers()
    {
        return $this->belongsToMany(User::class)->wherePivot('user_type_id', UserType::WATCHER_ID);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->orderByDesc('created_at');
    }

    public function anydesk()
    {
        return $this->belongsTo(AnyDesk::class, 'anydesk_id', 'id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    protected function date(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => Carbon::parse($value)->format('Y-m-d'),
        );
    }

    protected function startTime(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => Carbon::parse($value)->format('H:i'),
        );
    }

    protected function endTime(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => Carbon::parse($value)->format('H:i'),
        );
    }

    public static function allInTheWeekOf(?string $referenceDate)
    {
        $referenceDate = CarbonImmutable::parse($referenceDate);

        if ($referenceDate->isSaturday()) {
            $referenceDate = $referenceDate->modify('next Monday');
        }

        $startPoint = $referenceDate->isDayOfWeek(Carbon::MONDAY)
            ? $referenceDate
            : $referenceDate->modify('Last Monday');
        $endPoint = $startPoint->addDays(4);

        $allWeekGrowthSessions = GrowthSession::query()
            ->with(['attendees', 'watchers', 'comments', 'anydesk', 'tags'])
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

    public function hasAttendee(User $attendee): bool
    {
        return ! ! $this->attendees->find($attendee);
    }

    public function hasWatcher(User $watcher): bool
    {
        return ! ! $this->watchers->find($watcher);
    }
}
