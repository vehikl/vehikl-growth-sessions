<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Http;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
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

        if (! $this->canAuthenticate($githubUser)) {
            abort(
                Response::HTTP_UNAUTHORIZED,
                "Sorry :(, but at the moment only members of the Vehikl organization can Login."
            );
        }

        $email = $githubUser->getEmail();
        $socialMobUser = User::query()->where('email', $email)->first();
        if (! $socialMobUser) {
            $socialMobUser = User::query()->create([
                'name' => $githubUser->getName() ?? Str::before($email, '@'),
                'email' => $email,
                'avatar' => $githubUser->getAvatar(),
                'password' => Hash::make(Str::random()),
            ]);
        }
        auth()->login($socialMobUser, true);
        return redirect($this->redirectPath());
    }

    private function canAuthenticate(SocialiteUser $user): bool
    {
        if (! config('github.email') || ! config('github.password')) {
            return true;
        }
        return $this->isPartOfVehikl($user);
    }

    private function isPartOfVehikl(SocialiteUser $user): bool
    {
        $response = Http::withBasicAuth(config('github.email'), config('github.password'))
            ->get("https://api.github.com/users/{$user->getNickname()}/orgs")
            ->json();

        $VEHIKL_ID = 6425636;
        return collect($response)->where('id', $VEHIKL_ID)->isNotEmpty();
    }
}
