<?php

namespace Tests\Feature;

use App\GrowthSession;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Tests\TestCase;

class GrowthSessionTest extends TestCase
{
    public function testAnAuthenticatedUserCanCreateAGrowthSession()
    {
        $user = User::factory()->create();
        $topic = 'The fundamentals of foo';
        $title = 'Foo';
        $this->actingAs($user)->postJson(route('social_mobs.store'), [
            'topic' => $topic,
            'title' => $title,
            'location' => 'At the central mobbing area',
            'start_time' => now()->format('h:i a'),
            'date' => today(),
        ])->assertSuccessful();

        $this->assertEquals($topic, $user->growthSessions->first()->topic);
        $this->assertEquals($title, $user->growthSessions->first()->title);
    }

    public function testTheOwnerOfAGrowthSessionCanEditIt()
    {
        $growthSession = GrowthSession::factory()->create();
        $newTopic = 'A brand new topic!';
        $newTitle = 'A whole new title!';

        $this->actingAs($growthSession->owner)->putJson(route('social_mobs.update', ['social_mob' => $growthSession->id]), [
            'topic' => $newTopic,
            'title' => $newTitle,
        ])->assertSuccessful();

        $this->assertEquals($newTopic, $growthSession->fresh()->topic);
        $this->assertEquals($newTitle, $growthSession->fresh()->title);
    }

    public function testTheOwnerOfAGrowthSessionCanChangeTheAttendeeLimit()
    {
        $growthSession = GrowthSession::factory()->create(['attendee_limit' => 5]);

        $newAttendeeLimit = 10;
        $this->actingAs($growthSession->owner)->putJson(route('social_mobs.update', ['social_mob' => $growthSession->id]), [
            'attendee_limit' => $newAttendeeLimit
        ])->assertSuccessful();

        $this->assertEquals($newAttendeeLimit, $growthSession->fresh()->attendee_limit);
    }

    public function testTheOwnerOfAGrowthSessionCanNotChangeTheAttendeeLimitBelowTheCurrentAttendeeCount()
    {
        $growthSession = GrowthSession::factory()->create(['attendee_limit' => 6]);
        $users = User::factory()->times(5)->create();
        $growthSession->attendees()->attach($users->pluck('id'));

        $newAttendeeLimit = 4;
        $this->assertTrue($newAttendeeLimit < $growthSession->attendees()->count());
        $this->actingAs($growthSession->owner)->putJson(route('social_mobs.update', ['social_mob' => $growthSession->id]), [
            'attendee_limit' => $newAttendeeLimit
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['attendee_limit' => 'The attendee limit must be at least 5.']);
    }

    public function testTheNewAttendeeLimitHasToBeANumber()
    {
        $growthSession = GrowthSession::factory()->create(['attendee_limit' => 5]);

        $newAttendeeLimit = 'bananas';
        $this->actingAs($growthSession->owner)->putJson(route('social_mobs.update', ['social_mob' => $growthSession->id]), [
            'attendee_limit' => $newAttendeeLimit
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['attendee_limit' => 'The attendee limit must be an integer.']);
    }

    public function testItAllowsTheAttendeeLimitToBeUnset()
    {
        $growthSession = GrowthSession::factory()->create(['attendee_limit' => 5]);

        $this->actingAs($growthSession->owner)->putJson(route('social_mobs.update', ['social_mob' => $growthSession->id]), [
            'attendee_limit' => null
        ])->assertSuccessful();

        $this->assertEquals(GrowthSession::NO_LIMIT, $growthSession->fresh()->attendee_limit);
    }

    public function testTheOwnerCanChangeTheDateOfAnUpcomingGrowthSession()
    {
        $this->setTestNow('2020-01-01');
        $growthSession = GrowthSession::factory()->create(['date' => "2020-01-02"]);
        $newDate = '2020-01-10';

        $this->actingAs($growthSession->owner)->putJson(route('social_mobs.update', ['social_mob' => $growthSession->id]), [
            'date' => $newDate,
        ])->assertSuccessful();

        $this->assertEquals($newDate, $growthSession->fresh()->toArray()['date']);
    }

    public function testTheDateOfTheGrowthSessionCannotBeSetToThePast()
    {
        $this->setTestNow('2020-01-05');
        $growthSession = GrowthSession::factory()->create(['date' => "2020-01-06"]);
        $newDate = '2020-01-03';

        $this->actingAs($growthSession->owner)->putJson(route('social_mobs.update', ['social_mob' => $growthSession->id]), [
            'date' => $newDate,
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testTheOwnerCannotUpdateAGrowthSessionThatAlreadyHappened()
    {
        $this->setTestNow('2020-01-05');
        $growthSession = GrowthSession::factory()->create(['date' => "2020-01-01"]);
        $newDate = '2020-01-10';

        $this->actingAs($growthSession->owner)->putJson(route('social_mobs.update', ['social_mob' => $growthSession->id]), [
            'date' => $newDate,
        ])->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testAUserThatIsNotAnOwnerOfAGrowthSessionCannotEditIt()
    {
        $growthSession = GrowthSession::factory()->create();
        $notTheOwner = User::factory()->create();

        $this->actingAs($notTheOwner)->putJson(route('social_mobs.update', ['social_mob' => $growthSession->id]), [
            'topic' => 'Anything',
        ])->assertForbidden();
    }

    public function testTheOwnerCanDeleteAnExistingGrowthSession()
    {
        $this->withoutExceptionHandling();
        $growthSession = GrowthSession::factory()->create();

        $this->actingAs($growthSession->owner)->deleteJson(route('social_mobs.destroy', ['social_mob' => $growthSession->id]))
            ->assertSuccessful();

        $this->assertEmpty($growthSession->fresh());
    }

    public function testAUserThatIsNotAnOwnerOfAGrowthSessionCannotDeleteIt()
    {
        $growthSession = GrowthSession::factory()->create();
        $notTheOwner = User::factory()->create();

        $this->actingAs($notTheOwner)->deleteJson(route('social_mobs.destroy', ['social_mob' => $growthSession->id]))
            ->assertForbidden();
    }

    public function testAGivenUserCanRSVPToAGrowthSession()
    {
        $existingGrowthSession = GrowthSession::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('social_mobs.join', ['social_mob' => $existingGrowthSession->id]))
            ->assertSuccessful();

        $this->assertEquals($user->id, $existingGrowthSession->attendees->first()->id);
    }

    public function testAUserCannotJoinTheSameGrowthSessionTwice()
    {
        $existingGrowthSession = GrowthSession::factory()->create();
        $user = User::factory()->create();
        $existingGrowthSession->attendees()->attach($user);

        $this->actingAs($user)
            ->postJson(route('social_mobs.join', ['social_mob' => $existingGrowthSession->id]))
            ->assertForbidden();

        $this->assertCount(1, $existingGrowthSession->attendees);
    }

    public function testAUserCanLeaveTheGrowthSession()
    {
        $existingGrowthSession = GrowthSession::factory()->create();
        $user = User::factory()->create();
        $existingGrowthSession->attendees()->attach($user);

        $this->actingAs($user)
            ->postJson(route('social_mobs.leave', ['social_mob' => $existingGrowthSession->id]))
            ->assertSuccessful();

        $this->assertEmpty($existingGrowthSession->attendees);
    }

    public function testItCanProvideAllGrowthSessionsOfTheCurrentWeekForAuthenticatedUser()
    {
        $this->withoutExceptionHandling();
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');

        $mondaySocial = GrowthSession::factory()
            ->create(['date' => $monday, 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $lateWednesdaySocial = GrowthSession::factory()
            ->create(['date' => $monday->addDays(2), 'start_time' => '04:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $earlyWednesdaySocial = GrowthSession::factory()
            ->create(['date' => $monday->addDays(2), 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $fridaySocial = GrowthSession::factory()
            ->create(['date' => $monday->addDays(4), 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        GrowthSession::factory()
            ->create(['date' => $monday->addDays(8), 'start_time' => '03:30 pm', 'attendee_limit' => 4]); // Socials on another week

        $expectedResponse = [
            $monday->toDateString() => [$mondaySocial],
            $monday->addDays(1)->toDateString() => [],
            $monday->addDays(2)->toDateString() => [$earlyWednesdaySocial, $lateWednesdaySocial],
            $monday->addDays(3)->toDateString() => [],
            $monday->addDays(4)->toDateString() => [$fridaySocial],
        ];

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('social_mobs.week'));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function testItCanProvideAllGrowthSessionsOfTheCurrentWeekForAuthenticatedUserEvenOnFridays()
    {
        $this->setTestNow('Next Friday');

        $mondaySocial = GrowthSession::factory()
            ->create(['date' => Carbon::parse('Last Monday'), 'attendee_limit' => 4])
            ->toArray();
        $fridaySocial = GrowthSession::factory()
            ->create(['date' => today(), 'attendee_limit' => 4])
            ->toArray();

        $expectedResponse = [
            Carbon::parse('Last Monday')->toDateString() => [$mondaySocial],
            Carbon::parse('Last Tuesday')->toDateString() => [],
            Carbon::parse('Last Wednesday')->toDateString() => [],
            Carbon::parse('Last Thursday')->toDateString() => [],
            today()->toDateString() => [$fridaySocial],
        ];

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('social_mobs.week'));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function testItCanProvideAllGrowthSessionsOfASpecifiedWeekForAuthenticatedUserIfADateIsGiven()
    {
        $weekThatHasNoGrowthSessions = '2020-05-25';
        $this->setTestNow($weekThatHasNoGrowthSessions);
        $weekThatHasTheGrowthSessions = '2020-05-04';
        $mondayOfWeekWithGrowthSessions = CarbonImmutable::parse($weekThatHasTheGrowthSessions);

        $mondaySocial = GrowthSession::factory()
            ->create(['date' => $mondayOfWeekWithGrowthSessions, 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $lateWednesdaySocial = GrowthSession::factory()
            ->create(['date' => $mondayOfWeekWithGrowthSessions->addDays(2), 'start_time' => '04:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $earlyWednesdaySocial = GrowthSession::factory()
            ->create(['date' => $mondayOfWeekWithGrowthSessions->addDays(2), 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $fridaySocial = GrowthSession::factory()
            ->create(['date' => $mondayOfWeekWithGrowthSessions->addDays(4), 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();

        $expectedResponse = [
            $mondayOfWeekWithGrowthSessions->toDateString() => [$mondaySocial],
            $mondayOfWeekWithGrowthSessions->addDays(1)->toDateString() => [],
            $mondayOfWeekWithGrowthSessions->addDays(2)->toDateString() => [$earlyWednesdaySocial, $lateWednesdaySocial],
            $mondayOfWeekWithGrowthSessions->addDays(3)->toDateString() => [],
            $mondayOfWeekWithGrowthSessions->addDays(4)->toDateString() => [$fridaySocial],
        ];

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('social_mobs.week', ['date' => $weekThatHasTheGrowthSessions]));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function testItDoesNotProvideLocationOfAllGrowthSessionsOfASpecifiedWeekForAnonymousUser()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        GrowthSession::factory()->create(['date' => $monday, 'start_time' => '03:30 pm', 'attendee_limit' => 4]);
        GrowthSession::factory()->create(['date' => $monday->addDays(2), 'start_time' => '04:30 pm', 'attendee_limit' => 4]);
        GrowthSession::factory()->create(['date' => $monday->addDays(2), 'start_time' => '03:30 pm', 'attendee_limit' => 4]);
        GrowthSession::factory()->create(['date' => $monday->addDays(4), 'start_time' => '03:30 pm', 'attendee_limit' => 4]);
        GrowthSession::factory()->create(['date' => $monday->addDays(8), 'start_time' => '03:30 pm', 'attendee_limit' => 4]);

        $response = $this->getJson(route('social_mobs.week'));

        $response->assertSuccessful();
        $response->assertDontSee('At AnyDesk XYZ - abcdefg');
    }

    public function testItDoesNotProvideLocationOfAGrowthSessionForAnonymousUser()
    {
        $this->withoutExceptionHandling();
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        $growthSession = GrowthSession::factory()->create(['date' => $monday, 'start_time' => '03:30 pm']);

        $response = $this->get(route('social_mobs.show', ['social_mob' => $growthSession]));

        $response->assertSuccessful();
        $response->assertDontSee('At AnyDesk XYZ - abcdefg');
    }

    public function testItCanProvideGrowthSessionLocationForAuthenticatedUser()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        $growthSession = GrowthSession::factory()->create(['date' => $monday, 'start_time' => '03:30 pm']);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('social_mobs.show', $growthSession));

        $response->assertSuccessful();
        $response->assertSee('At AnyDesk XYZ - abcdefg');
    }

    public function testItProvidesASummaryOfTheMobsOfTheDay()
    {
        $today = '2020-01-02';
        $tomorrow = '2020-01-03';
        $this->setTestNow($today);
        $user = User::factory()->create();

        $todayMobs = GrowthSession::factory()->times(2)->create(['date' => $today, 'attendee_limit' => 4]);
        GrowthSession::factory()->times(2)->create(['date' => $tomorrow, 'attendee_limit' => 4]);

        $response = $this->actingAs($user)->getJson(route('social_mobs.day'));

        $response->assertJson($todayMobs->toArray());
    }

    public function testTheSlackBotCanSeeTheMobLocation()
    {
        $this->seed();
        $growthSession = GrowthSession::factory()->create(['date' => today()]);
        $this->getJson(route('social_mobs.day'), ['Authorization' => 'Bearer '.config('auth.slack_token')])
            ->assertJsonFragment(['location' => $growthSession->location]);
    }

    public function testAnAttendeeLimitCanBeSetWhenCreatingAtMob()
    {
        $user = User::factory()->create();

        $expectedAttendeeLimit = 420;
        $this->actingAs($user)->postJson(
            route('social_mobs.store'),
            $this->defaultParameters(['attendee_limit' => $expectedAttendeeLimit])
        )->assertSuccessful();

        $this->assertEquals($expectedAttendeeLimit, $user->growthSessions->first()->attendee_limit);
    }

    public function testAnAttendeeLimitCannotBeLessThanFour()
    {
        $user = User::factory()->create();

        $expectedAttendeeLimit = 3;
        $this->actingAs($user)->postJson(
            route('social_mobs.store'),
            $this->defaultParameters(['attendee_limit' => $expectedAttendeeLimit])
        )->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['attendee_limit' => 'The attendee limit must be at least 4']);
    }

    public function testAGrowthSessionCannotBeJoinedIfTheAttendeeLimitIsMet()
    {
        $user = User::factory()->create();
        $attendess = User::factory()->times(4)->create();
        /** @var GrowthSession $growthSession */
        $growthSession = GrowthSession::factory()->create(['attendee_limit' => 4]);
        $growthSession->attendees()->attach($attendess);


        $response = $this->actingAs($user)
            ->postJson(route('social_mobs.join', ['social_mob' => $growthSession->id]));

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJson(['message' => 'The attendee limit has been reached.']);
    }

    /**
     * @param int $expectedAttendeeLimit
     * @return array
     */
    private function defaultParameters(array $params = []): array
    {
        return array_merge([
            'topic' => 'The fundamentals of foo',
            'title' => 'Foo',
            'location' => 'At the central mobbing area',
            'start_time' => now()->format('h:i a'),
            'date' => today(),
        ], $params);
    }
}
