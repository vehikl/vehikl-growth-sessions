<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    public function redirectToProvider($driver)
    {
        if (App::environment('local') && empty(config('services.github.client_id'))) {
            Auth::login(User::query()
                ->whereIn('github_nickname', config('auth.vehikl_names'))
                ->first()
            );
            return redirect()->back();
        }
        return Socialite::driver($driver)->redirect();
    }

    public function handleProviderCallback($driver = 'github')
    {
        $socialUser = Socialite::driver($driver)->user();
        $email = $socialUser->getEmail();
        $githubNickName = $socialUser->getNickname();

        $growthSessionUser = User::query()->where('github_nickname', $githubNickName)->first();

        if (! $growthSessionUser) {
            $growthSessionUser = User::query()->create([
                'name' => $socialUser->getName() ?? Str::before($email, '@'),
                'github_nickname' => $socialUser->getNickname(),
                'avatar' => $socialUser->getAvatar(),
                'password' => Hash::make(Str::random()),
            ]);
            Email::query()->create(['address' => $email, 'user_id' => $growthSessionUser->id]);
        }
        auth()->login($growthSessionUser, true);
        return redirect('/');
    }
}
