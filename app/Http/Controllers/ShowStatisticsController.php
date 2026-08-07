<?php

namespace App\Http\Controllers;

use App\Actions\Statistics;
use App\Http\Requests\ShowStatisticsRequest;
use App\Models\GrowthSession;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ShowStatisticsController extends Controller
{
    private const TOP_HOSTS_LIMIT = 5;

    /**
     * How long a range nobody warms is worth keeping. `statistics:recalculate` warms only the
     * default range twice daily; every other range a visitor asks for would otherwise hold a
     * full copy of the matrix for three days.
     */
    private const CUSTOM_RANGE_CACHE_SECONDS = 300;

    private ?string $firstSessionDate = null;

    public function __construct(private readonly Statistics $statistics) {}

    public function __invoke(ShowStatisticsRequest $request): Response
    {
        $weekStart = today()->startOfWeek()->toDateString();
        $weekEnd = today()->endOfWeek()->toDateString();

        $firstSessionDate = $this->firstSessionDate();
        $lastPossibleDate = today()->toDateString();

        // Clamped to the window the project actually covers, so a link carrying wild dates
        // collapses onto a range that is already cached rather than minting its own entry.
        $startDate = $this->clamp($request->startDate($firstSessionDate), $firstSessionDate, $lastPossibleDate);
        $endDate = $this->clamp($request->endDate($lastPossibleDate), $firstSessionDate, $lastPossibleDate);

        return Inertia::render('Statistics', [
            'summary' => fn () => $this->summary($weekStart, $weekEnd),
            'top_hosts' => fn () => $this->topHosts($weekStart, $weekEnd),
            'tags' => fn () => $this->tagUsage($weekStart, $weekEnd),
            'yet_to_mob_with' => fn () => $this->yetToMobWith($request->user(), $firstSessionDate),
            'members' => fn () => $this->members($startDate, $endDate),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'first_session_date' => $firstSessionDate,
        ]);
    }

    private function clamp(string $date, string $earliest, string $latest): string
    {
        return min(max($date, $earliest), $latest);
    }

    private function summary(string $weekStart, string $weekEnd): array
    {
        $weeklySessions = GrowthSession::query()
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->with('attendees:id')
            ->withCount('attendees')
            ->get();

        return [
            'lifetime_sessions_count' => GrowthSession::query()->count(),
            'sessions_this_week_count' => $weeklySessions->count(),
            'weekly_unique_participants_count' => $weeklySessions->flatMap->attendees->pluck('id')->unique()->count(),
            'lifetime_minutes_count' => $this->lifetimeMinutes(),
        ];
    }

    /**
     * Every minute ever spent in a Growth Session, summed from the scheduled windows and
     * left in minutes so the page can render the leftover half hour rather than rounding it
     * away. Sessions missing either end of their window contribute nothing.
     */
    private function lifetimeMinutes(): int
    {
        $seconds = (int) GrowthSession::query()
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->sum(DB::raw('TIME_TO_SEC(TIMEDIFF(end_time, start_time))'));

        return (int) round($seconds / 60);
    }

    private function topHosts(string $weekStart, string $weekEnd): array
    {
        return User::query()
            ->select(['id', 'name'])
            ->visibleInStatistics()
            ->withCount([
                'sessionsHosted' => fn ($query) => $query->whereBetween('date', [$weekStart, $weekEnd]),
            ])
            ->having('sessions_hosted_count', '>', 0)
            ->orderByDesc('sessions_hosted_count')
            ->orderBy('name')
            ->limit(self::TOP_HOSTS_LIMIT)
            ->get()
            ->map(fn (User $host) => [
                'id' => $host->id,
                'name' => $host->name,
                'sessions_hosted_count' => $host->sessions_hosted_count,
            ])
            ->all();
    }

    private function tagUsage(string $weekStart, string $weekEnd): array
    {
        return Tag::query()
            ->withCount([
                'growthSessions as sessions_count' => fn ($query) => $query->whereBetween('date', [$weekStart, $weekEnd]),
            ])
            ->having('sessions_count', '>', 0)
            ->orderByDesc('sessions_count')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'sessions_count' => $tag->sessions_count,
            ])
            ->all();
    }

    /**
     * Every statistics-visible member's participation over the requested range, which is
     * what lets the community team follow one person's progress rather than only their own.
     *
     * `has_mobbed_with` is dropped: it is the other half of an N-by-N matrix that nothing
     * on the page reads, and shipping it would roughly double the payload.
     */
    private function members(string $startDate, string $endDate): array
    {
        return $this->statisticsFor($startDate, $endDate)
            ->map(fn (array $member) => Arr::except($member, ['has_mobbed_with']))
            ->values()
            ->all();
    }

    /**
     * The one door onto the statistics matrix. `members` and `yetToMobWith` resolve to the same
     * range whenever no custom one was asked for, and the action memoises per range, so the
     * matrix is built once per request rather than once per prop.
     */
    private function statisticsFor(string $startDate, string $endDate): Collection
    {
        $isWarmedRange = $startDate === $this->firstSessionDate() && $endDate === today()->toDateString();

        return $this->statistics->getFormattedStatisticsFor(
            $startDate,
            $endDate,
            $isWarmedRange ? null : self::CUSTOM_RANGE_CACHE_SECONDS,
        );
    }

    /**
     * Vehikl members the current user has never mobbed with, over the lifetime of the
     * project. Deliberately independent of the range the members table is showing: this
     * is the personal "who's left" list, and it reuses the range that
     * `statistics:recalculate` warms twice daily, so it is normally served from cache.
     */
    private function yetToMobWith(User $user, string $firstSessionDate): array
    {
        $statistics = $this->statisticsFor($firstSessionDate, today()->toDateString());

        return collect($statistics->firstWhere('user_id', $user->id)['has_not_mobbed_with'] ?? [])
            ->values()
            ->all();
    }

    private function firstSessionDate(): string
    {
        return $this->firstSessionDate ??= GrowthSession::query()->orderBy('date')->first()?->date?->toDateString()
            ?? today()->toDateString();
    }
}
