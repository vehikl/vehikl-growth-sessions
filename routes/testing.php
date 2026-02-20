<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::prefix('e2e')->group(function () {
    Route::get('/login', function () {
        $user = User::firstOrCreate(
            ['github_nickname' => 'e2e-test-user'],
            [
                'name' => 'E2E Test User',
                'avatar' => 'https://i.pravatar.cc/50?u=e2e-test',
                'password' => 'password',
                'is_vehikl_member' => true,
            ]
        );

        $user->update(['is_vehikl_member' => true]);

        Auth::login($user);

        return redirect('/');
    });

    Route::post('/reset', function () {
        $user = User::where('github_nickname', 'e2e-test-user')->first();

        if ($user) {
            $user->growthSessions()->each(function ($session) {
                $session->members()->detach();
                $session->comments()->delete();
                $session->delete();
            });
        }

        return response()->json(['status' => 'ok']);
    });
});
