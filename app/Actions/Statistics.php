<?php

namespace App\Actions;

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
            function () {
                $exceptionUserIds = implode(',', config('statistics.loosen_participation_rules.user_ids', []));
                $loosenParticipationRules = config('statistics.loosen_participation_rules.user_ids')
                    ? "OR ((main_user_id IN ({$exceptionUserIds}) OR other_user_id IN ({$exceptionUserIds})) AND growth_session_id IS NOT NULL)"
                    : '';
                $maxMobSize = config('statistics.max_mob_size');
                $atendeeId = UserType::ATTENDEE_ID;
                $ownerId = UserType::OWNER_ID;

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
                    ->groupBy(['main_user_id', 'main_user_name', 'other_user_id', 'other_user_name'])
                    ->get();

                return $userStatistics
                    ->mapToGroups(fn(UserHasMobbedWithView $userHasMobbedWithView) => [
                        $userHasMobbedWithView->main_user_id => $userHasMobbedWithView
                    ])->map(function ($grouped) {
                        $hasMobbedWith = $grouped->filter(fn(UserHasMobbedWithView $view) => $view->has_mobbed);
                        $hasNotMobbedWith = $grouped->reject(fn(UserHasMobbedWithView $view) => $view->has_mobbed);
                        return [
                            'name' => $grouped[0]->main_user_name,
                            'user_id' => $grouped[0]->main_user_id,
                            'has_mobbed_with_count' => $hasMobbedWith->count(),
                            'has_mobbed_with' => $hasMobbedWith->map(fn(UserHasMobbedWithView $mobbedWith) => [
                                'user_id' => $mobbedWith->other_user_id,
                                'name' => $mobbedWith->other_user_name,
                            ])->values(),
                            'has_not_mobbed_with_count' => $hasNotMobbedWith->count(),
                            'has_not_mobbed_with' => $hasNotMobbedWith->map(fn(UserHasMobbedWithView $didNotMobWith
                            ) => [
                                'user_id' => $didNotMobWith->other_user_id,
                                'name' => $didNotMobWith->other_user_name,
                            ])->values(),
                        ];
                    })
                    ->values();
            });
    }
}
