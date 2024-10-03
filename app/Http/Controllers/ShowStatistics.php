<?php

namespace App\Http\Controllers;

use App\GrowthSession;
use App\UserHasMobbedWithView;
use Illuminate\Http\Request;

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
        $userStatistics = UserHasMobbedWithView::query()
            ->selectRaw('
                    main_user_id, 
                    main_user_name,
                    other_user_id, 
                    other_user_name,
                    MAX(CASE 
                    WHEN total_number_of_attendees < 10 
                         AND main_user_type_id = 2 
                         AND other_user_type_id = 2
                    THEN 1 
                    ELSE 0 
                   END) AS has_mobbed')
            ->groupBy(['main_user_id', 'main_user_name', 'other_user_id', 'other_user_name'])

            ->get();

        $a = 0;
        $formattedStatistics = $userStatistics
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
                    'has_not_mobbed_with' => $hasNotMobbedWith->map(fn(UserHasMobbedWithView $didNotMobWith) => [
                        'user_id' => $didNotMobWith->other_user_id,
                        'name' => $didNotMobWith->other_user_name,
                    ])->values(),
                ];
            })
            ->values();

        return response()->json([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'users' => $formattedStatistics
        ]);
    }
}
