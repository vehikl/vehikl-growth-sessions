<?php

namespace App\Http\Controllers;

use App\GrowthSession;
use App\User;
use Illuminate\Http\Request;

class ShowStatistics extends Controller
{
    public function __invoke(Request $request)
    {
        $start_date = GrowthSession::query()->orderBy('date')->first()?->date?->toDateString() ?? today()->toDateString();
        $end_date = today()->toDateString();
        $emailExclusions = [
            'go@vehikl.com',
            config('auth.slack_app_email')
        ];

        $users = User::query()
            ->vehikaliens()
            ->whereNotIn('email', $emailExclusions)
            ->withCount(['sessionsAttended', 'sessionsHosted', 'sessionsWatched'])
            ->orderBy('id')
            ->get()
            ->map(fn(User $user) => [
                'name' => $user->name,
                'user_id' => $user->id,
                'total_participation' => $user->sessions_attended_count + $user->sessions_hosted_count + $user->sessions_watched_count,
                'hosted' => $user->sessions_hosted_count,
                'attended' => $user->sessions_attended_count,
                'watched' => $user->sessions_watched_count,
            ]);

        return response()->json([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'users' => $users->toArray()
        ]);
    }
}
