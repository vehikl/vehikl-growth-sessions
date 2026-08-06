<?php

namespace App\Http\Controllers;

use App\Actions\Statistics;
use App\Http\Resources\HostedGrowthSession;
use App\Models\GrowthSession;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowDashboardController extends Controller
{
    private const SESSIONS_PER_PAGE = 10;

    private const TOP_TAGS = 5;

    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'summary' => $this->summary($request->user()),
            'hosted_sessions' => $this->hostedSessions($request),
            'top_tags' => $this->topTags($request->user()),
            'yet_to_mob_with' => $this->yetToMobWith($request->user()),
        ]);
    }

    /**
     * Totals across the whole history, not just the page on screen.
     */
    private function summary(User $user): array
    {
        return [
            'sessions_hosted_count' => $user->sessionsHosted()->count(),
            'upcoming_count' => $user->sessionsHosted()->whereDate('growth_sessions.date', '>=', today())->count(),
            // The same count the rows report, summed over the whole history rather than a page.
            'total_attendees_count' => (int) $user->sessionsHosted()
                ->withCount($this->attendeesExcludingHost($user))
                ->get()
                ->sum('attendee_count'),
        ];
    }

    /**
     * Every Growth Session this user owns, newest first, one page at a time.
     *
     * Date is the primary sort, and start time only breaks ties within a day: they are two
     * separate columns, and ordering by start time alone would interleave the whole history
     * by the hour of the day.
     */
    private function hostedSessions(Request $request): LengthAwarePaginator
    {
        $user = $request->user();

        return $user->sessionsHosted()
            ->with('tags')
            ->withCount($this->attendeesExcludingHost($user))
            ->orderByDesc('growth_sessions.date')
            ->orderByDesc('growth_sessions.start_time')
            ->paginate(self::SESSIONS_PER_PAGE)
            ->through(fn (GrowthSession $session) => HostedGrowthSession::make($session)->resolve($request));
    }

    /**
     * The subjects this user hosts under most often, busiest first.
     *
     * Counted over the sessions they own rather than every session they were in: the
     * Dashboard reports what they have chosen to run, not what they happened to attend.
     * Ties break alphabetically so the list does not reshuffle between requests.
     */
    private function topTags(User $user): array
    {
        return Tag::query()
            ->withCount([
                'growthSessions as sessions_count' => fn (Builder $query) => $query->whereIn(
                    'growth_sessions.id',
                    $user->sessionsHosted()->select('growth_sessions.id')
                ),
            ])
            ->having('sessions_count', '>', 0)
            ->orderByDesc('sessions_count')
            ->orderBy('name')
            ->limit(self::TOP_TAGS)
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
     *
     * A non-member is absent from the matrix and so receives an empty list.
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

    /**
     * The attendee relationship spans the owner pivot role too, so an unconstrained count
     * would report 1 for a session nobody joined.
     */
    private function attendeesExcludingHost(User $user): array
    {
        return [
            'attendees as attendee_count' => fn (Builder $query) => $query->where('users.id', '!=', $user->id),
        ];
    }
}
