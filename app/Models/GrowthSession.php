<?php

namespace App\Models;

use App\Observers\GrowthSessionObserver;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[ObservedBy(GrowthSessionObserver::class)]
class GrowthSession extends Model
{
    use HasFactory;

    const NO_LIMIT = PHP_INT_MAX;

    protected $appends = ['owner'];
    protected $hidden = ['share_token'];

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
        'allow_watchers',
        'slack_thread_ts',
    ];

    protected $attributes = [
        'end_time' => '17:00',
        'attendee_limit' => self::NO_LIMIT,
    ];

    protected function owner(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->owners()->first(),
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

    public function notifiableUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->wherePivotIn('user_type_id', [UserType::ATTENDEE_ID, UserType::OWNER_ID, UserType::WATCHER_ID]);
    }
    
    public function toNotificationMetadata(): array
    {
        return [
            'title' => $this->title,
            'location' => $this->location,
            'date' => $this->date?->format('Y-m-d'),
            'start_time' => $this->start_time?->format('h:i a'),
            'end_time' => $this->end_time?->format('h:i a'),
        ];
    }

    public function notifiableUserIdsExcludingInitiator(User $initiator): Collection
    {
        return $this->notifiableUsers->filter(function (User $user) use ($initiator) {
            return $user->id !== $initiator->id;
        })->pluck('id')->values();
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
            set: fn($value) => Carbon::parse($value)->format('Y-m-d'),
        );
    }

    protected function startTime(): Attribute
    {
        return Attribute::make(
            set: fn($value) => Carbon::parse($value)->format('H:i'),
        );
    }

    protected function endTime(): Attribute
    {
        return Attribute::make(
            set: fn($value) => Carbon::parse($value)->format('H:i'),
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

    /**
     * The scheduled minutes across whatever set of Growth Sessions is handed in — every summary
     * that reports time spent goes through here, so the statistics page and the dashboard cannot
     * come to measure an hour differently.
     *
     * Left in minutes rather than hours so the caller can render the leftover half hour instead
     * of rounding it away. Sessions missing either end of their window contribute nothing.
     *
     * @param Builder|BelongsToMany $sessions
     */
    public static function scheduledMinutes($sessions): int
    {
        $seconds = (int)$sessions
            ->whereNotNull('growth_sessions.start_time')
            ->whereNotNull('growth_sessions.end_time')
            ->sum(DB::raw('TIME_TO_SEC(TIMEDIFF(growth_sessions.end_time, growth_sessions.start_time))'));

        return (int)round($seconds / 60);
    }

    public function hasAttendee(User $attendee): bool
    {
        return !!$this->attendees->find($attendee);
    }

    public function hasWatcher(User $watcher): bool
    {
        return !!$this->watchers->find($watcher);
    }

    public function hasParticipant(User $user): bool
    {
        return $this->hasAttendee($user) || $this->hasWatcher($user);
    }

    public function hasUnlimitedSlots(): bool
    {
        return $this->attendee_limit === self::NO_LIMIT;
    }

    public function hasOpenSlots(): bool
    {
        return $this->remainingSlots() != 0;
    }

    public function remainingSlots(): int
    {
        return $this->hasUnlimitedSlots()
            ? -1
            : max(0, $this->attendee_limit - $this->attendees()->count());
    }
}
