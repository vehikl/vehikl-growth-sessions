<?php

namespace App\Http\Controllers;

use App\Actions\Statistics;
use App\Http\Requests\ShowStatisticsRequest;
use App\Models\GrowthSession;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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
            // Every minute anybody has ever spent growing. Purely social sessions and Lightning
            // Talks are booked as Growth Sessions but are not that time, so they are left out —
            // the lifetime session count above still counts them.
            'lifetime_minutes_count' => GrowthSession::scheduledMinutes(
                GrowthSession::query()->excludingPurelySocial()->excludingLightningTalks()
            ),
        ];
    }

    private function topHosts(string $weekStart, string $weekEnd): array
    {
        return User::query()
            ->select(['id', 'name', 'avatar'])
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
                'avatar' => $host->avatar,
                'sessions_hosted_count' => $host->sessions_hosted_count,
            ])
            ->all();
    }

    private function tagUsage(string $weekStart, string $weekEnd): array
    {
        return Tag::usageRanking(fn ($query) => $query->whereBetween('date', [$weekStart, $weekEnd]));
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
     * The one door onto the statistics matrix. The action memoises per range, so the matrix is
     * built once per request rather than once per prop.
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

    private function firstSessionDate(): string
    {
        return $this->firstSessionDate ??= GrowthSession::query()->orderBy('date')->first()?->date?->toDateString()
            ?? today()->toDateString();
    }
}
