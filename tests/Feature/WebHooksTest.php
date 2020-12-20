<?php

namespace Tests\Feature;

use App\GrowthSession;
use App\User;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebHooksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->setTestNow('2020-01-08T15:00:00');
        $this->enableHooks();
    }

    public function testItHitsTheMobDeletedTodayWebHookWheneverAGrowthSessionIsDeletedToday()
    {
        $growthSession = GrowthSession::factory()->create(['date' => today()]);
        $user = $growthSession->owner;
        $this->actingAs($user)->deleteJson(route('social_mobs.destroy', $growthSession))->assertSuccessful();

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.deleted_today');
        });
    }

    public function testItDoesNotHittheMobDeletedTodayWebHookIfTheMobWasDeletedAtAnyOtherDay()
    {
        $socialMob = GrowthSession::factory()->create(['date' => today()->addDay()]);
        $user = $socialMob->owner;
        $this->actingAs($user)->deleteJson(route('social_mobs.destroy', $socialMob))->assertSuccessful();

        Http::assertNothingSent();
    }

    public function testItDoesNotHitTheWebHookIfTheHookIsNotDefined()
    {
        $this->disableHooks();
        $socialMob = GrowthSession::factory()->create();
        $user = $socialMob->owner;
        $this->actingAs($user)->deleteJson(route('social_mobs.destroy', $socialMob))->assertSuccessful();

        Http::assertNothingSent();
    }

    public function testItDoesNotBreakIfTheHookFails()
    {
        Http::fake(function () {
            return Http::response('Oh no, the webhook failed! :(', Response::HTTP_INTERNAL_SERVER_ERROR);
        });
        $socialMob = GrowthSession::factory()->create();
        $user = $socialMob->owner;

        $this->actingAs($user)
            ->deleteJson(route('social_mobs.destroy', $socialMob))->assertSuccessful();
    }

    public function testItHitsTheMobUpdatedTodayWebHookWheneverAMobIsUpdatedToday()
    {
        $socialMob = GrowthSession::factory()->create(['date' => today()]);
        $user = $socialMob->owner;
        $this->actingAs($user)->putJson(route('social_mobs.update', $socialMob), ['topic' => 'new topic'])->assertSuccessful();

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.updated_today');
        });
    }

    public function testItHitsTheUpdatedTodayWebHookIfAMobChangedItsDateToToday()
    {
        $socialMob = GrowthSession::factory()->create(['date' => today()->addDay()]);
        $user = $socialMob->owner;
        $this->actingAs($user)->putJson(route('social_mobs.update', $socialMob), ['date' => today()])->assertSuccessful();

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.updated_today');
        });
    }

    public function testItHitsTheCreatedTodayWebHookIfAMobWasCreatedForToday()
    {
        $this->setTestNow('2020-01-01T10:30:00.000');
        $user = User::factory()->create();
        $socialMobData = GrowthSession::factory()->make(['date' => today()])->toArray();
        $this->actingAs($user)->postJson(route('social_mobs.store'), $socialMobData)->assertSuccessful();

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.created_today');
        });
    }

    public function testItHitsTheAttendeesWebHookIfSomeoneJoinsAMobThatWillHappenToday()
    {
        $this->withoutExceptionHandling();
        $socialMob = GrowthSession::factory()->create();
        $newMember = User::factory()->create();

        $this->actingAs($newMember)->postJson(route('social_mobs.join', $socialMob))->assertSuccessful();

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.attendees_today');
        });
    }


    public function testItHitsTheAttendeesWebHookIfSomeoneLeavesAMobThatWillHappenToday()
    {
        $socialMob = GrowthSession::factory()->create();
        /** @var GrowthSession $socialMob */
        $attendee = User::factory()->create();
        $socialMob->attendees()->attach($attendee);

        $this->actingAs($attendee)->postJson(route('social_mobs.leave', $socialMob))->assertSuccessful();

        Http::assertSent(function (Request $request) {
            return $request->url() === config('webhooks.attendees_today');
        });
    }


    /**
     * @dataProvider providesActionsOutsideOfWebHookTimes
     */
    public function testItDoesNotSendWebHookNotificationsOutsideOfTheSetStartAndEndTimes(
        $startTime,
        $endTime,
        $requestTime
    )
    {
        $this->setTestNow("2020-01-01 {$requestTime}");
        Config::set('webhooks.start_time', $startTime);
        Config::set('webhooks.end_time', $endTime);
        $user = User::factory()->create();
        $socialMobData = GrowthSession::factory()->make(['date' => today()])->toArray();

        $socialMob = $this->actingAs($user)
            ->postJson(route('social_mobs.store'), $socialMobData)->assertSuccessful();
        $this->actingAs($user)
            ->putJson(route('social_mobs.update', ['social_mob' => $socialMob['id']]), ['topic' => 'new topic'])->assertSuccessful();
        $this->actingAs($user)
            ->deleteJson(route('social_mobs.destroy', ['social_mob' => $socialMob['id']]))->assertSuccessful();

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

    public function testItIncludesTheOwnerInformationOnThePayload()
    {
        $user = User::factory()->create();
        $socialMobData = GrowthSession::factory()->make(['date' => today()])->toArray();
        $this->actingAs($user)->postJson(route('social_mobs.store'), $socialMobData)->assertSuccessful();

        Http::assertSent(function (Request $request) use ($socialMobData) {
            return !empty($request->data()['owner']) && $request->data()['location'] === $socialMobData['location'];
        });
    }

    private function enableHooks(): void
    {
        Config::set('webhooks.deleted_today', 'http://foobar.test/deleted');
        Config::set('webhooks.updated_today', 'http://foobar.test/updated');
        Config::set('webhooks.created_today', 'http://foobar.test/created');
        Config::set('webhooks.attendees_today', 'http://foobar.test/join_leave');
    }

    private function disableHooks(): void
    {
        Config::set('webhooks.deleted_today', null);
        Config::set('webhooks.updated_today', null);
        Config::set('webhooks.created_today', null);
        Config::set('webhooks.attendees_today', null);
    }
}
