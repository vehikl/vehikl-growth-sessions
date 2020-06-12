<?php

namespace Tests\Feature;

use App\SocialMob;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
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

    public function testItHitsTheCreatedTodayWebHookIfAMobWasCreatedForToday()
    {
        Carbon::setTestNow('2020-01-01T10:30:00.000');
        CarbonImmutable::setTestNow('2020-01-01T10:30:00.000');
        $this->enableHooks();
        $user = factory(User::class)->create();
        $socialMobData = factory(SocialMob::class)->make(['date' => today()])->toArray();
        $this->actingAs($user)->postJson(route('social_mob.store'), $socialMobData);

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.created_today');
        });
    }

    /**
     * @dataProvider providesActionsOutsideOfWebHookTimes
     */
    public function testItDoesNotSendWebHookNotificationsOutsideOfTheSetStartAndEndTimes(
        $startTime,
        $endTime,
        $requestTime
    ) {
        $this->enableHooks();
        Carbon::setTestNow("2020-01-01 {$requestTime}");
        CarbonImmutable::setTestNow("2020-01-01 {$requestTime}");
        Config::set('webhooks.start_time', $startTime);
        Config::set('webhooks.end_time', $endTime);
        $user = factory(User::class)->create();
        $socialMobData = factory(SocialMob::class)->make(['date' => today()])->toArray();

        $socialMob = $this->actingAs($user)
            ->postJson(route('social_mob.store'), $socialMobData);
        $this->actingAs($user)
            ->putJson(route('social_mob.update', ['social_mob' => $socialMob['id']]), ['topic' => 'new topic']);
        $this->actingAs($user)
            ->deleteJson(route('social_mob.destroy', ['social_mob' => $socialMob['id']]));

        Http::assertNothingSent();
    }

    public function providesActionsOutsideOfWebHookTimes()
    {
        $startTime = '09:30 am';
        $endTime = '05:00 pm';
        return [
            'The request happened before the start time' => [$startTime, $endTime, '08:00am'],
            'The request happened after the end time' => [$startTime, $endTime, '06:00pm'],
        ];
    }

    private function enableHooks(): void
    {
        Config::set('webhooks.deleted_today', 'http://foobar.test/deleted');
        Config::set('webhooks.updated_today', 'http://foobar.test/updated');
        Config::set('webhooks.created_today', 'http://foobar.test/created');
    }

    private function disableHooks(): void
    {
        Config::set('webhooks.deleted_today', null);
        Config::set('webhooks.updated_today', null);
        Config::set('webhooks.created_today', null);
    }
}
