<?php

namespace App\Http\Controllers;

use App\GrowthSession;
use App\User;
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

        $cacheDurationInSeconds = 60 * 5;
        $userStatistics = Cache::remember("statistics-{$start_date}-{$end_date}", $cacheDurationInSeconds,
            function () use ($start_date, $end_date) {
                $githubUserExclusions = [
                    config('auth.slack_app_name'),
                    ...config('auth.vehikl_names'),
                    'vehikl-morning-mob'
                ];

                $allUsers = User::query()
                    ->vehikaliens()
                    ->whereNotIn('github_nickname', $githubUserExclusions)
                    ->withCount([
                        'sessionsAttended' => fn($query) => $query->whereBetween('date', [$start_date, $end_date]),
                        'sessionsHosted' => fn($query) => $query->whereBetween('date', [$start_date, $end_date]),
                        'sessionsWatched' => fn($query) => $query->whereBetween('date', [$start_date, $end_date])
                    ])
                    ->with('allSessions', fn($query) => $query
                        ->with('members')
                    )
                    ->orderBy('id')
                    ->get();


                return $allUsers
                    ->append('has_mobbed_with')
                    ->map(function (User $user) use ($allUsers) {
                        $hasMobbedWith = $user->has_mobbed_with
                            ->map(fn(User $peer) => ['name' => $peer->name, 'user_id' => $peer->id])
                            ->values();

                        $hasNotMobbedWith = $allUsers
                            ->whereNotIn('id', $user->has_mobbed_with->pluck('id'))
                            ->reject(fn(User $peer) => $peer->id === $user->id || !$peer->is_vehikl_member)
                            ->map(fn(User $peer) => ['name' => $peer->name, 'user_id' => $peer->id])
                            ->values();

                        return [
                            'name' => $user->name,
                            'user_id' => $user->id,
                            'has_mobbed_with' => $hasMobbedWith,
                            'has_mobbed_with_count' => count($hasMobbedWith),
                            'has_not_mobbed_with' => $hasNotMobbedWith,
                            'has_not_mobbed_with_count' => count($hasNotMobbedWith),
                            'total_sessions_count' => $user->sessions_attended_count + $user->sessions_hosted_count + $user->sessions_watched_count,
                            'sessions_hosted_count' => $user->sessions_hosted_count,
                            'sessions_attended_count' => $user->sessions_attended_count,
                            'sessions_watched_count' => $user->sessions_watched_count,
                        ];
                    });
            });


        return response()->json([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'users' => $userStatistics
        ]);
    }
}
