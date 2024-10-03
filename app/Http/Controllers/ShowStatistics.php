<?php

namespace App\Http\Controllers;

use App\GrowthSession;
use App\UserHasNotMobbedWithView;
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

        $cacheDurationInSeconds = 60 * 5;

        $userStatistics = UserHasNotMobbedWithView::query()
            ->with('mainUser', 'hasNotMobbedWith')
            ->get();

        $totalAmountOfUsers = UserHasNotMobbedWithView::query()->distinct('main_user_id')->count();

        $formattedStatistics = $userStatistics
            ->mapToGroups(fn(UserHasNotMobbedWithView $userHasNotMobbedWith) => [
                $userHasNotMobbedWith->main_user_id => $userHasNotMobbedWith
            ])->map(function ($grouped) use ($totalAmountOfUsers) {
                $hasNotMobbedWithCount = count($grouped);
                return [
                    'name' => $grouped[0]->mainUser->name,
                    'user_id' => $grouped[0]->mainUser->id,
                    'has_mobbed_with_count' => $totalAmountOfUsers - $hasNotMobbedWithCount,
                    'has_not_mobbed_with_count' => $hasNotMobbedWithCount,
                    'has_not_mobbed_with' => $grouped->map(fn(UserHasNotMobbedWithView $notMobbedWithDetails) => [
                        'name' => $notMobbedWithDetails->hasNotMobbedWith->name,
                        'user_id' => $notMobbedWithDetails->hasNotMobbedWith->id,
                    ]),
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
