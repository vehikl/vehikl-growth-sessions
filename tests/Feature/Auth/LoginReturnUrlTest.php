<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class LoginReturnUrlTest extends TestCase
{
    public function testTheLoginLinkRemembersThePageTheVisitorCameFrom()
    {
        $this->fakeGithub();

        $this->get(route('oauth.login.redirect', ['driver' => 'github', 'redirect' => '/?date=2020-01-15&session=42']));

        $this->assertSame(url('/?date=2020-01-15&session=42'), session('url.intended'));
    }

    public function testAnOffSiteRedirectIsIgnoredSoTheLoginLinkCannotBounceVisitorsAway()
    {
        $this->fakeGithub();

        $this->get(route('oauth.login.redirect', ['driver' => 'github', 'redirect' => 'https://evil.test/phish']));
        $this->assertNull(session('url.intended'));

        $this->get(route('oauth.login.redirect', ['driver' => 'github', 'redirect' => '//evil.test/phish']));
        $this->assertNull(session('url.intended'));
    }

    public function testTheVisitorLandsBackOnThePageTheyLoggedInFrom()
    {
        $user = User::factory()->create(['github_nickname' => 'invited-guest']);
        $this->fakeGithub($user->github_nickname);

        $this->get(route('oauth.login.redirect', ['driver' => 'github', 'redirect' => '/?session=42']));

        $this->get('/oauth/github/callback')
            ->assertRedirect(url('/?session=42'));
    }

    public function testTheVisitorLandsOnTheBoardWhenThereIsNowhereToReturnTo()
    {
        $user = User::factory()->create(['github_nickname' => 'walk-in']);
        $this->fakeGithub($user->github_nickname);

        $this->get(route('oauth.login.redirect', ['driver' => 'github']));

        $this->get('/oauth/github/callback')
            ->assertRedirect('/');
    }

    private function fakeGithub(string $nickname = 'someone'): void
    {
        $socialiteUser = (new SocialiteUser())->map([
            'name' => 'Some One',
            'nickname' => $nickname,
            'email' => "{$nickname}@example.com",
            'avatar' => 'https://example.com/avatar.png',
        ]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);
        $provider->shouldReceive('redirect')->andReturn(redirect('https://github.test/login/oauth'));

        Socialite::shouldReceive('driver')->andReturn($provider);
    }
}
