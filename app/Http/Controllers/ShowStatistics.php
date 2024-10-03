<?php

namespace App\Http\Controllers;

use App\GrowthSession;
use App\UserHasMobbedWithView;
use App\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ShowStatistics extends Controller
{
    public function __invoke(Request $request)
    {
        if (!$request->expectsJson()) {
            return view('statistics');
        }

        $start_date = $request->input(
            'start_date',
            GrowthSession::query()->orderBy('date')->first()?->date?->toDateString()
            ?? today()->toDateString()
        );

        $end_date = today()->toDateString();

        $cacheDurationInHours = 6;
        $cacheDurationInSeconds = 60 * 60 * $cacheDurationInHours;
        $formattedStatistics = Cache::remember("statistics-{$start_date}-{$end_date}", $cacheDurationInSeconds,
            function () use ($start_date, $end_date) {
                $exceptionUserIds = implode(',', config('statistics.loosen_participation_rules.user_ids', []));
                $loosenParticipationRules = config('statistics.loosen_participation_rules.user_ids')
                    ? "OR ((main_user_id IN ({$exceptionUserIds}) OR other_user_id IN ({$exceptionUserIds})) AND growth_session_id IS NOT NULL)"
                    : '';
                $maxMobSize = config('statistics.max_mob_size');
                $atendeeId = UserType::ATTENDEE_ID;

                $userStatistics = UserHasMobbedWithView::query()
                    ->selectRaw(<<<SelectStatement
                    main_user_id, 
                    main_user_name,
                    other_user_id, 
                    other_user_name,
                    MAX(CASE 
                    WHEN (
                         total_number_of_attendees < {$maxMobSize} 
                         AND main_user_type_id = {$atendeeId}
                         AND other_user_type_id = {$atendeeId}
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


        return response()->json([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'users' => $formattedStatistics
        ]);
    }
}
