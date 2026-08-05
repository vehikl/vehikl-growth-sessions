<?php

namespace App\Http\Controllers;

use App\Http\Resources\HostedGrowthSession;
use App\Models\GrowthSession;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowDashboardController extends Controller
{
    private const SESSIONS_PER_PAGE = 15;

    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'summary' => $this->summary($request->user()),
            'hosted_sessions' => $this->hostedSessions($request),
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
