<?php

namespace App\Http\Controllers;

use App\Actions\Statistics;
use App\Http\Resources\HostedGrowthSession;
use App\Models\GrowthSession;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserHasMobbedWithView;
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
     * The pivot roles that put someone in the mob, at either end of the pairing. Watching is
     * not mobbing, which is the line the statistics matrix behind `yet_to_mob_with` draws too.
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
            'growth_minutes_count' => $this->growthMinutes($user),
        ];
    }

    /**
     * Every minute this user has spent in the room, hosting or attending alike.
     *
     * Sessions dated today or earlier only: an hour booked for next week has grown nobody yet.
     * The line is drawn on the date rather than the clock, so a session later today already
     * counts — the same day-level reading `upcoming_count` takes of "today". Watching is not
     * mobbing and does not count, which is the line `mob_squad` and `yet_to_mob_with` draw too.
     */
    private function growthMinutes(User $user): int
    {
        return GrowthSession::scheduledMinutes(
            $user->allSessions()
                ->wherePivotIn('user_type_id', self::MOBBING_ROLES)
                ->whereDate('growth_sessions.date', '<=', today())
                ->excludingPurelySocial()
        );
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
     * The people this user has mobbed with most often, busiest first.
     *
     * Counted off the same view `yet_to_mob_with` is built from, so the two sections cannot
     * disagree about what a mob is — keep it that way rather than reaching for the pivot.
     *
     * @return array<int, array{id: int, name: string, avatar: string|null, sessions_together_count: int}>
     */
    private function mobSquad(User $user): array
    {
        $pairings = UserHasMobbedWithView::query()
            ->where('main_user_id', $user->id)
            ->whereIn('main_user_type_id', self::MOBBING_ROLES)
            ->whereIn('other_user_type_id', self::MOBBING_ROLES)
            ->where('total_number_of_attendees', '<', config('statistics.max_mob_size'))
            ->whereIn('other_user_id', User::query()->vehikaliens()->select('id'))
            ->whereHas('growthSession', fn (Builder $query) => $query->whereDate('date', '<=', today()))
            ->groupBy('other_user_id', 'other_user_name')
            ->orderByDesc('sessions_together_count')
            ->orderBy('other_user_name')
            ->limit(self::MOB_SQUAD_LIMIT)
            ->get([
                'other_user_id',
                'other_user_name',
                DB::raw('COUNT(DISTINCT growth_session_id) AS sessions_together_count'),
            ]);

        // The view carries names but no avatars, and it is a join over users twice over.
        $avatars = User::query()->whereKey($pairings->pluck('other_user_id'))->pluck('avatar', 'id');

        return $pairings
            ->map(fn (UserHasMobbedWithView $pairing) => [
                'id' => $pairing->other_user_id,
                'name' => $pairing->other_user_name,
                'avatar' => $avatars->get($pairing->other_user_id),
                'sessions_together_count' => (int) $pairing->sessions_together_count,
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
