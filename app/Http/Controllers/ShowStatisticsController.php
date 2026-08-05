<?php

namespace App\Http\Controllers;

use App\Actions\Statistics;
use App\Models\GrowthSession;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowStatisticsController extends Controller
{
    private const TOP_HOSTS_LIMIT = 5;

    public function __invoke(Request $request): Response
    {
        $weekStart = today()->startOfWeek()->toDateString();
        $weekEnd = today()->endOfWeek()->toDateString();

        return Inertia::render('Statistics', [
            'summary' => $this->summary($weekStart, $weekEnd),
            'top_hosts' => $this->topHosts($weekStart, $weekEnd),
            'tags' => $this->tagUsage($weekStart, $weekEnd),
            'yet_to_mob_with' => $this->yetToMobWith($request->user()),
        ]);
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
            'average_attendance_count' => round($weeklySessions->avg('attendees_count') ?? 0, 1),
        ];
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
     * Vehikl members the current user has never mobbed with, over the lifetime of the
     * project. This reuses the date range that `statistics:recalculate` warms twice
     * daily, so it is normally served from cache rather than recomputing the matrix.
     */
    private function yetToMobWith(User $user): array
    {
        $oldestSessionDate = GrowthSession::query()->orderBy('date')->first()?->date?->toDateString()
            ?? today()->toDateString();

        $statistics = app(Statistics::class)
            ->getFormattedStatisticsFor($oldestSessionDate, today()->toDateString());

        return collect($statistics->firstWhere('user_id', $user->id)['has_not_mobbed_with'] ?? [])
            ->values()
            ->all();
    }
}
