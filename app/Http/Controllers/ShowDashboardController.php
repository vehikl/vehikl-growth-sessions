<?php

namespace App\Http\Controllers;

use App\Actions\Statistics;
use App\Http\Resources\HostedGrowthSession;
use App\Models\GrowthSession;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ShowDashboardController extends Controller
{
    private const SESSIONS_PER_PAGE = 10;

    private const TOP_TAGS_LIMIT = 5;

    private const MOB_SQUAD_LIMIT = 5;

    private const DEFAULT_SORT = 'date';

    /**
     * The pivot roles that put someone in the mob. Watching is not mobbing, which is the line
     * the statistics matrix behind `yet_to_mob_with` already draws: one Dashboard cannot call
     * the same pairing a mob in one section and an absent one in the other.
     */
    private const MOBBING_ROLES = [UserType::OWNER_ID, UserType::ATTENDEE_ID];

    /**
     * The orders the hosted sessions list offers, keyed by the value the query string carries.
     *
     * Each names its own direction because the useful end differs per field: you want your most
     * recent and best-attended sessions first, but names read alphabetically.
     */
    private const SORTS = [
        'date' => ['column' => 'growth_sessions.date', 'direction' => 'desc'],
        'name' => ['column' => 'growth_sessions.title', 'direction' => 'asc'],
        'attendees' => ['column' => 'attendee_count', 'direction' => 'desc'],
    ];

    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'summary' => $this->summary($request->user()),
            'hosted_sessions' => $this->hostedSessions($request),
            'sort' => $this->sort($request),
            'top_tags' => $this->topTags($request->user()),
            'mob_squad' => $this->mobSquad($request->user()),
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
            'sessions_attended_count' => $user->sessionsAttended()->count(),
            'upcoming_count' => $user->sessionsHosted()->whereDate('growth_sessions.date', '>=', today())->count(),
            // The same count the rows report, summed over the whole history rather than a page.
            'total_attendees_count' => (int) $user->sessionsHosted()
                ->withCount($this->attendeesExcludingHost($user))
                ->get()
                ->sum('attendee_count'),
        ];
    }

    /**
     * Every Growth Session this user owns, one page at a time, in the requested order.
     *
     * Whatever the chosen field, date then start time break its ties: they are two separate
     * columns, and ordering by start time alone would interleave the whole history by the hour
     * of the day. For the date sort those tie-breakers are the sort itself.
     */
    private function hostedSessions(Request $request): LengthAwarePaginator
    {
        $user = $request->user();
        $sort = self::SORTS[$this->sort($request)];

        return $user->sessionsHosted()
            ->with('tags')
            ->withCount($this->attendeesExcludingHost($user))
            ->orderBy($sort['column'], $sort['direction'])
            ->orderByDesc('growth_sessions.date')
            ->orderByDesc('growth_sessions.start_time')
            ->paginate(self::SESSIONS_PER_PAGE)
            // Without this the pagination links drop the sort and the next page reorders itself.
            ->withQueryString()
            ->through(fn (GrowthSession $session) => HostedGrowthSession::make($session)->resolve($request));
    }

    /**
     * The requested order, or the default. Anything unrecognised is ignored rather than
     * rejected: the sort reaches the server as a hand-editable query string.
     */
    private function sort(Request $request): string
    {
        $requested = $request->query('sort');

        return is_string($requested) && array_key_exists($requested, self::SORTS)
            ? $requested
            : self::DEFAULT_SORT;
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
        return Tag::usageRanking(
            fn (Builder $query) => $query->whereIn('growth_sessions.id', $user->sessionsHosted()->select('growth_sessions.id')),
            self::TOP_TAGS_LIMIT,
        );
    }

    /**
     * The people this user has shared the most Growth Sessions with, busiest first.
     *
     * Counted over every session they were in the room for, hosted or joined alike, and over
     * the whole history rather than a window: this is who they mob with, not who they mobbed
     * with lately. Anyone they have never mobbed with is absent rather than sitting at zero,
     * so a short list means a short history. Ties break alphabetically so the leaderboard does
     * not reshuffle between requests.
     *
     * Guests are left out: this is a board of co-workers, and naming them here would undo the
     * anonymising that GrowthSession does for every non-member elsewhere in the app.
     *
     * @return array<int, array{id: int, name: string, avatar: string|null, sessions_together_count: int}>
     */
    private function mobSquad(User $user): array
    {
        $sessionsMobbed = $user->allSessions()
            ->wherePivotIn('user_type_id', self::MOBBING_ROLES)
            ->select('growth_sessions.id');

        return User::query()
            ->join('growth_session_user', 'growth_session_user.user_id', '=', 'users.id')
            ->whereIn('growth_session_user.growth_session_id', $sessionsMobbed)
            ->whereIn('growth_session_user.user_type_id', self::MOBBING_ROLES)
            ->whereKeyNot($user->id)
            ->vehikaliens()
            ->groupBy('users.id', 'users.name', 'users.avatar')
            ->orderByDesc('sessions_together_count')
            ->orderBy('users.name')
            ->limit(self::MOB_SQUAD_LIMIT)
            ->get([
                'users.id',
                'users.name',
                'users.avatar',
                DB::raw('COUNT(growth_session_user.growth_session_id) AS sessions_together_count'),
            ])
            ->map(fn (User $peer) => [
                'id' => $peer->id,
                'name' => $peer->name,
                'avatar' => $peer->avatar,
                'sessions_together_count' => (int) $peer->sessions_together_count,
            ])
            ->all();
    }

    /**
     * Vehikl members the current user has never mobbed with, over the lifetime of the
     * project. This reuses the date range that `statistics:recalculate` warms twice
     * daily, so it is normally served from cache rather than recomputing the matrix.
     *
     * A non-member is absent from the matrix and so receives an empty list.
     *
     * The avatars are looked up here rather than added to the matrix itself, which is cached for
     * hours at a time: a payload already warm from before the deploy would have no such key.
     *
     * @return array<int, array{id: int, name: string, avatar: string|null}>
     */
    private function yetToMobWith(User $user): array
    {
        $oldestSessionDate = GrowthSession::query()->orderBy('date')->first()?->date?->toDateString()
            ?? today()->toDateString();

        $statistics = app(Statistics::class)
            ->getFormattedStatisticsFor($oldestSessionDate, today()->toDateString());

        $members = collect($statistics->firstWhere('user_id', $user->id)['has_not_mobbed_with'] ?? [])->values();
        $avatars = User::query()->whereKey($members->pluck('id'))->pluck('avatar', 'id');

        return $members
            ->map(fn (array $member) => [
                'id' => $member['id'],
                'name' => $member['name'],
                'avatar' => $avatars->get($member['id']),
            ])
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
