<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{

    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function redirectToProvider()
    {
        return Socialite::driver('github')->redirect();
    }

    public function handleProviderCallback()
    {
        $githubUser = Socialite::driver('github')->user();
        $email = $githubUser->getEmail();
        $growthSessionUser = User::query()->where('email', $email)->first();

        if (! $growthSessionUser) {
            $growthSessionUser = User::query()->create([
                'name' => $githubUser->getName() ?? Str::before($email, '@'),
                'github_nickname' => $githubUser->getNickname(),
                'email' => $email,
                'avatar' => $githubUser->getAvatar(),
                'password' => Hash::make(Str::random()),
            ]);
        }
        auth()->login($growthSessionUser, true);
        return redirect($this->redirectPath());
    }
}
