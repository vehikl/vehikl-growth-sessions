<?php

namespace App\Http\Controllers;

use App\Models\GrowthSession;
use App\Models\Tag;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ShowStatisticsController extends Controller
{
    private const TOP_HOSTS_LIMIT = 5;

    public function __invoke(): Response
    {
        $weekStart = today()->startOfWeek()->toDateString();
        $weekEnd = today()->endOfWeek()->toDateString();

        return Inertia::render('Statistics', [
            'summary' => $this->summary($weekStart, $weekEnd),
            'top_hosts' => $this->topHosts($weekStart, $weekEnd),
            'tags' => $this->tagUsage($weekStart, $weekEnd),
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
}
