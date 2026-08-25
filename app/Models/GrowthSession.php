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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[ObservedBy(GrowthSessionObserver::class)]
class GrowthSession extends Model
{
    use HasFactory;

    const NO_LIMIT = PHP_INT_MAX;

    /** The relations GrowthSessionResource reads - eager-load these before building one to avoid lazy loading. */
    const RESOURCE_RELATIONS = ['owners', 'attendees', 'watchers', 'waitlist', 'comments', 'anydesk', 'tags'];

    /** The subset of RESOURCE_RELATIONS the visibility policy reads via `owner`/`hasParticipant()`. */
    const VISIBILITY_RELATIONS = ['owners', 'attendees', 'watchers', 'waitlist'];

    const SOCIAL_TAG = 'Social';

    const LIGHTNING_TALKS_TITLE = 'Lightning Talks';

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
            get: fn () => $this->owners->first(),
        );
    }

    protected function topicSegments(): Attribute
    {
        return Attribute::make(get: fn () => $this->parseTrustedSegments($this->topic))->shouldCache();
    }

    protected function locationSegments(): Attribute
    {
        return Attribute::make(get: fn () => $this->parseTrustedSegments($this->location))->shouldCache();
    }

    /**
     * Only Vehikl members can own a session (see GrowthSessionPolicy::create()), so the topic's and
     * location's author is always trusted - images are still suppressed since these are single-line
     * fields with no room for one.
     */
    private function parseTrustedSegments(string $content): array
    {
        return TextSegmentParser::parse($content, isTrustedAuthor: true, allowImages: false);
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

    /**
     * Everyone waiting for a seat, front of the queue first. The tie-break on user id only matters
     * when two enrolments share a timestamp to the microsecond, and exists so the order a member
     * is shown in is never arbitrary.
     */
    public function waitlist(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->wherePivot('user_type_id', UserType::WAITLISTED_ID)
            ->orderByPivot('waitlisted_at')
            ->orderByPivot('user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->orderByDesc('created_at')->orderByDesc('id');
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

        // Only the relations the visibility policy reads are eager-loaded here - the caller filters
        // out sessions the viewer can't see before the rest of RESOURCE_RELATIONS is needed, so
        // hydrating them this early would just be discarded work for every filtered-out session.
        $allWeekGrowthSessions = GrowthSession::query()
            ->with(self::VISIBILITY_RELATIONS)
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

    public function scopeExcludingLightningTalks(Builder $query): Builder
    {
        return $query->where('growth_sessions.title', 'not like', '%'.self::LIGHTNING_TALKS_TITLE.'%');
    }

    /**
     * The scheduled minutes across whatever set of Growth Sessions is handed in — every summary
     * that reports time spent measures an hour through here, so no two of them can come to read
     * the clock differently. Which sessions belong in the total is still the caller's call: the
     * Dashboard leaves the purely social ones out of a member's own growth time, and the
     * statistics page leaves out the purely social ones and the Lightning Talks alike.
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

    public function hasWaitlistedMember(User $user): bool
    {
        return (bool) $this->waitlist->find($user);
    }

    /**
     * Whoever will actually be in the room. Someone still queueing is not one of them, which is why
     * this is the question the location asks rather than {@see hasParticipant()}.
     */
    public function hasAttendeeOrWatcher(User $user): bool
    {
        return $this->hasAttendee($user) || $this->hasWatcher($user);
    }

    /**
     * Whether the member holds any role at all - including a place in the queue. That place is a
     * deliberate act, so it grants sight of the growth session and rules out taking a second role.
     */
    public function hasParticipant(User $user): bool
    {
        return $this->hasAttendeeOrWatcher($user) || $this->hasWaitlistedMember($user);
    }

    /**
     * Everyone with a stake in this session — owners, attendees, watchers — except whoever just
     * caused the notification. An owner editing their own session shouldn't hear about it, but a
     * comment from an attendee should still reach the owners, so exclusion follows the initiator
     * rather than any fixed role.
     */
    public function participantsToNotify(?User $initiator): Collection
    {
        return $this->attendees
            ->merge($this->watchers)
            ->reject(fn (User $user) => $initiator !== null && $user->is($initiator))
            ->unique('id')
            ->values();
    }

    /**
     * The old/new values for whichever of `$fields` actually changed on this update.
     *
     * @param string[] $fields
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function changedValuesFor(array $fields): array
    {
        $changedFields = array_intersect(array_keys($this->getChanges()), $fields);

        return collect($changedFields)->mapWithKeys(fn (string $field) => [
            $field => ['old' => $this->getRawOriginal($field), 'new' => $this->getAttributes()[$field] ?? null]
        ])->all();
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
