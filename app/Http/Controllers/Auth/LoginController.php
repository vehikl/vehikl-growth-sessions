<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Email;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    public function redirectToProvider(Request $request, $driver)
    {
        $this->rememberWhereToReturnTo($request);

        if (App::environment('local') && empty(config('services.github.client_id'))) {
            $githubUser = $request->input('github_user');

            Auth::login(User::query()
                ->when(
                    $githubUser,
                    fn($query) => $query->where('github_nickname', $githubUser),
                    fn($query) => $query->where('is_vehikl_member', true),
                )
                ->firstOrFail()
            );

            return redirect()->intended(route('home'));
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

        return redirect()->intended('/');
    }

    /**
     * Someone who follows an invite link and then logs in to join should come back to the page they were on,
     * drawer and all, rather than to a bare board. Only paths within this app are honoured, so the login link
     * cannot be dressed up to bounce a visitor somewhere else.
     */
    private function rememberWhereToReturnTo(Request $request): void
    {
        $returnTo = (string) $request->query('redirect', '');

        if (! str_starts_with($returnTo, '/') || str_starts_with($returnTo, '//')) {
            return;
        }

        redirect()->setIntendedUrl(url($returnTo));
    }
}
