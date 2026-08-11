<?php

namespace App\Models;

use App\Observers\GrowthSessionObserver;
use App\Support\TextSegmentParser;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

#[ObservedBy(GrowthSessionObserver::class)]
class GrowthSession extends Model
{
    use HasFactory;

    const NO_LIMIT = PHP_INT_MAX;

    /** The relations GrowthSessionResource reads - eager-load these before building one to avoid lazy loading. */
    const RESOURCE_RELATIONS = ['attendees', 'watchers', 'comments', 'anydesk', 'tags'];

    const SOCIAL_TAG = 'Social';

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
            get: fn () => $this->owners()->first(),
        );
    }

    protected function topicSegments(): Attribute
    {
        return Attribute::make(get: fn () => TextSegmentParser::parse($this->topic, allowImages: false))->shouldCache();
    }

    protected function locationSegments(): Attribute
    {
        return Attribute::make(get: fn () => TextSegmentParser::parse($this->location, allowImages: false))->shouldCache();
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
            ->with(self::RESOURCE_RELATIONS)
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

    public function scopeExcludingPurelySocial(Builder $query): Builder
    {
        return $query->whereNot(fn (Builder $session) => $session
            ->whereHas('tags', fn (Builder $tags) => $tags->where('tags.name', self::SOCIAL_TAG))
            ->whereDoesntHave('tags', fn (Builder $tags) => $tags->where('tags.name', '!=', self::SOCIAL_TAG))
        );
    }

    /**
     * The scheduled minutes across whatever set of Growth Sessions is handed in — every summary
     * that reports time spent measures an hour through here, so no two of them can come to read
     * the clock differently. Which sessions belong in the total is still the caller's call: the
     * Dashboard leaves the purely social ones out of a member's own growth time, where the
     * statistics page counts every hour the company spent.
     *
     * Left in minutes rather than hours so the caller can render the leftover half hour instead
     * of rounding it away. Sessions missing either end of their window contribute nothing.
     *
     * @param  Builder|BelongsToMany  $sessions
     */
    public static function scheduledMinutes($sessions): int
    {
        $seconds = (int) $sessions
            ->whereNotNull('growth_sessions.start_time')
            ->whereNotNull('growth_sessions.end_time')
            ->sum(DB::raw('TIME_TO_SEC(TIMEDIFF(growth_sessions.end_time, growth_sessions.start_time))'));

        return (int) round($seconds / 60);
    }

    public function hasAttendee(User $attendee): bool
    {
        return (bool) $this->attendees->find($attendee);
    }

    public function hasWatcher(User $watcher): bool
    {
        return (bool) $this->watchers->find($watcher);
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
