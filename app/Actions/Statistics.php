<?php

namespace App\Actions;

use App\User;
use App\UserHasMobbedWithView;
use App\UserType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Statistics
{
    public function getFormattedStatisticsFor(string $startDate, string $endDate): Collection
    {
        return Cache::remember(
            "statistics-{$startDate}-{$endDate}",
            config('statistics.cache_duration_in_seconds'
            ),
            function () use ($startDate, $endDate) {
                $exceptionUserIds = implode(',', config('statistics.loosen_participation_rules.user_ids', []));
                $loosenParticipationRules = config('statistics.loosen_participation_rules.user_ids')
                    ? "OR ((main_user_id IN ({$exceptionUserIds}) OR other_user_id IN ({$exceptionUserIds})) AND growth_session_id IS NOT NULL)"
                    : '';
                $maxMobSize = config('statistics.max_mob_size');
                $atendeeId = UserType::ATTENDEE_ID;
                $ownerId = UserType::OWNER_ID;


                $participationCountStatistics = User::query()
                    ->withCount([
                        'sessionsAttended' => fn($query) => $query->whereBetween('date', [$startDate, $endDate]),
                        'sessionsHosted' => fn($query) => $query->whereBetween('date', [$startDate, $endDate]),
                        'sessionsWatched' => fn($query) => $query->whereBetween('date', [$startDate, $endDate])
                    ])
                    ->get()
                    ->keyBy('id');

                $userStatistics = UserHasMobbedWithView::query()
                    ->selectRaw(<<<SelectStatement
                    main_user_id,
                    main_user_name,
                    other_user_id,
                    other_user_name,
                    MAX(CASE
                    WHEN (
                         total_number_of_attendees < {$maxMobSize}
                         AND (
                             (main_user_type_id = {$atendeeId} OR main_user_type_id = {$ownerId})
                             AND (other_user_type_id = {$atendeeId} OR other_user_type_id = {$ownerId})
                         )
                         )
                         {$loosenParticipationRules}
                    THEN 1
                    ELSE 0
                   END) AS has_mobbed
                   SelectStatement
                    )
                    ->whereHas('growthSession', fn($query) => $query->whereBetween('date', [$startDate, $endDate]))
                    ->groupBy(['main_user_id', 'main_user_name', 'other_user_id', 'other_user_name'])
                    ->get();

                $allVisibleUsers = User::query()
                    ->where('is_visible_in_statistics', true)
                    ->get();

                return $allVisibleUsers->map(function ($user) use ($userStatistics, $participationCountStatistics, $allVisibleUsers) {
                    $userStats = $userStatistics->where('main_user_id', $user->id);
                    $hasMobbedWith = $userStats->filter(fn($stat) => $stat->has_mobbed);

                    // Get all users that this user has mobbed with
                    $hasMobbedWithIds = $hasMobbedWith->pluck('other_user_id')->toArray();

                    // Get all users that this user has not mobbed with
                    $hasNotMobbedWith = $allVisibleUsers
                        ->reject(fn($otherUser) => $otherUser->id === $user->id || in_array($otherUser->id, $hasMobbedWithIds))
                        ->map(fn($otherUser) => [
                            'user_id' => $otherUser->id,
                            'name' => $otherUser->name,
                        ]);

                    $attendedCount = $participationCountStatistics[$user->id]->sessions_attended_count;
                    $hostedCount = $participationCountStatistics[$user->id]->sessions_hosted_count;
                    $watchedCount = $participationCountStatistics[$user->id]->sessions_watched_count;

                    return [
                        'name' => $user->name,
                        'user_id' => $user->id,
                        'sessions_attended_count' => $attendedCount,
                        'sessions_hosted_count' => $hostedCount,
                        'sessions_watched_count' => $watchedCount,
                        'total_sessions_count' => $attendedCount + $hostedCount + $watchedCount,
                        'has_mobbed_with_count' => $hasMobbedWith->count(),
                        'has_mobbed_with' => $hasMobbedWith->map(fn($mobbedWith) => [
                            'user_id' => $mobbedWith->other_user_id,
                            'name' => $mobbedWith->other_user_name,
                        ])->values(),
                        'has_not_mobbed_with_count' => $hasNotMobbedWith->count(),
                        'has_not_mobbed_with' => $hasNotMobbedWith->values(),
                    ];
                })->values();
            });
    }
}
