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
            ->with('mainUser', 'otherUser')
            ->get();

        $formattedStatistics = $userStatistics
            ->mapToGroups(fn(UserHasMobbedWithView $userHasMobbedWithView) => [
                $userHasMobbedWithView->main_user_id => $userHasMobbedWithView
            ])->map(function ($grouped) {
                $hasMobbedWith = $grouped->filter(fn(UserHasMobbedWithView $view) => $view->has_mobbed);
                $hasNotMobbedWith = $grouped->reject(fn(UserHasMobbedWithView $view) => $view->has_mobbed);
                return [
                    'name' => $grouped[0]->mainUser->name,
                    'user_id' => $grouped[0]->mainUser->id,
                    'has_mobbed_with_count' => $hasMobbedWith->count(),
                    'has_mobbed_with' => $hasMobbedWith->map(fn(UserHasMobbedWithView $mobbedWith) => [
                        'user_id' => $mobbedWith->otherUser->id,
                        'name' => $mobbedWith->otherUser->name,
                    ])->values(),
                    'has_not_mobbed_with_count' => $hasNotMobbedWith->count(),
                    'has_not_mobbed_with' => $hasNotMobbedWith->map(fn(UserHasMobbedWithView $didNotMobWith) => [
                        'user_id' => $didNotMobWith->otherUser->id,
                        'name' => $didNotMobWith->otherUser->name,
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
