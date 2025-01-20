<?php

namespace App\Http\Controllers;

use App\Actions\Statistics;
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

        $formattedStatistics = app(Statistics::class)->getFormattedStatisticsFor($start_date, $end_date);

        return response()->json([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'users' => $formattedStatistics
        ]);
    }
}
