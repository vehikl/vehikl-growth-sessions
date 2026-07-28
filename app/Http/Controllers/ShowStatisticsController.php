<?php

namespace App\Http\Controllers;

use App\Actions\Statistics;
use App\Models\GrowthSession;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShowStatisticsController extends Controller
{
    public function __invoke(Request $request)
    {
        if (! $request->expectsJson()) {
            return Inertia::render('Statistics');
        }

        $start_date = $request->input(
            'start_date',
            GrowthSession::query()->orderBy('date')->first()?->date?->toDateString()
            ?? today()->toDateString()
        );

        $end_date = $request->input(
            'end_date',
            today()->toDateString()
        );

        $formattedStatistics = app(Statistics::class)->getFormattedStatisticsFor($start_date, $end_date);

        $weeklySessions = GrowthSession::query()
            ->whereBetween('date', [today()->startOfWeek()->toDateString(), today()->endOfWeek()->toDateString()])
            ->with('attendees:id')
            ->withCount('attendees')
            ->get();
        $weeklyParticipants = $weeklySessions->flatMap->attendees->pluck('id')->unique();
        $currentUserStatistics = $formattedStatistics->firstWhere('user_id', $request->user()->id);

        $summary = [
            'lifetime_sessions_count' => GrowthSession::query()->count(),
            'sessions_this_week_count' => $weeklySessions->count(),
            'weekly_unique_participants_count' => $weeklyParticipants->count(),
            'average_attendance_count' => round($weeklySessions->avg('attendees_count') ?? 0, 1),
        ];

        $tags = Tag::query()
            ->withCount([
                'growthSessions as sessions_count' => fn ($query) => $query->whereBetween('date', [$start_date, $end_date]),
            ])
            ->having('sessions_count', '>', 0)
            ->orderByDesc('sessions_count')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'summary' => $summary,
            'tags' => $tags,
            'current_user' => $currentUserStatistics,
            'users' => $formattedStatistics,
        ]);
    }
}
