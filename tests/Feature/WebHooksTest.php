<?php

namespace Tests\Feature;

use App\SocialMob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebHooksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function testItHitsTheMobDeletedTodayWebHookWheneverAMobIsDeletedToday()
    {
        $this->enableHooks();
        $socialMob = factory(SocialMob::class)->create(['date' => today()]);
        $user = $socialMob->owner;
        $this->actingAs($user)->deleteJson(route('social_mob.destroy', $socialMob));

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.deleted_today');
        });
    }

    public function testItDoesNotHittheMobDeletedTodayWebHookIfTheMobWasDeletedAtAnyOtherDay()
    {
        $this->enableHooks();
        $socialMob = factory(SocialMob::class)->create(['date' => today()->addDay()]);
        $user = $socialMob->owner;
        $this->actingAs($user)->deleteJson(route('social_mob.destroy', $socialMob));

        Http::assertNothingSent();
    }

    public function testItDoesNotHitTheWebHookIfTheHookIsNotDefined()
    {
        $this->disableHooks();
        $socialMob = factory(SocialMob::class)->create();
        $user = $socialMob->owner;
        $this->actingAs($user)->deleteJson(route('social_mob.destroy', $socialMob));

        Http::assertNothingSent();
    }

    public function testItDoesNotBreakIfTheHookFails()
    {
        $this->enableHooks();
        Http::fake(function () {
            return Http::response('Oh no, the webhook failed! :(', Response::HTTP_INTERNAL_SERVER_ERROR);
        });
        $socialMob = factory(SocialMob::class)->create();
        $user = $socialMob->owner;

        $this->actingAs($user)
            ->deleteJson(route('social_mob.destroy', $socialMob))
            ->assertSuccessful();
    }

    public function testItHitsTheMobUpdatedTodayWebHookWheneverAMobIsUpdatedToday()
    {
        $this->enableHooks();
        $socialMob = factory(SocialMob::class)->create(['date' => today()]);
        $user = $socialMob->owner;
        $this->actingAs($user)->putJson(route('social_mob.update', $socialMob), ['topic' => 'new topic']);

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.updated_today');
        });
    }

    public function testItHitsTheUpdatedTodayWebHookIfAMobChangedItsDateToToday()
    {
        $this->enableHooks();
        $socialMob = factory(SocialMob::class)->create(['date' => today()->addDay()]);
        $user = $socialMob->owner;
        $this->actingAs($user)->putJson(route('social_mob.update', $socialMob), ['date' => today()]);

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.updated_today');
        });
    }

    private function enableHooks(): void
    {
        Config::set('webhooks.deleted_today', 'http://foobar.test/deleted');
        Config::set('webhooks.updated_today', 'http://foobar.test/updated');
    }

    private function disableHooks(): void
    {
        Config::set('webhooks.deleted_today', null);
        Config::set('webhooks.updated_today', null);
    }
}
