<?php

namespace Tests\Feature;

use App\SocialMob;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Tests\TestCase;

class SocialMobTest extends TestCase
{
    public function testAnAuthenticatedUserCanCreateASocialMob()
    {
        $user = factory(User::class)->create();
        $topic = 'The fundamentals of foo';
        $title = 'Foo';
        $this->actingAs($user)->postJson(route('social_mobs.store'), [
            'topic' => $topic,
            'title' => $title,
            'location' => 'At the central mobbing area',
            'start_time' => now()->format('h:i a'),
            'date' => today(),
        ])->assertSuccessful();

        $this->assertEquals($topic, $user->socialMobs->first()->topic);
        $this->assertEquals($title, $user->socialMobs->first()->title);
    }

    public function testTheOwnerOfAMobCanEditIt()
    {
        $mob = factory(SocialMob::class)->create();
        $newTopic = 'A brand new topic!';
        $newTitle = 'A whole new title!';

        $this->actingAs($mob->owner)->putJson(route('social_mobs.update', ['social_mob' => $mob->id]), [
            'topic' => $newTopic,
            'title' => $newTitle,
        ])->assertSuccessful();

        $this->assertEquals($newTopic, $mob->fresh()->topic);
        $this->assertEquals($newTitle, $mob->fresh()->title);
    }

    public function testTheOwnerOfAMobCanChangeTheAttendeeLimit()
    {
        $mob = factory(SocialMob::class)->create(['attendee_limit' => 5]);

        $newAttendeeLimit = 10;
        $this->actingAs($mob->owner)->putJson(route('social_mobs.update', ['social_mob' => $mob->id]), [
            'attendee_limit' => $newAttendeeLimit
        ])->assertSuccessful();

        $this->assertEquals($newAttendeeLimit, $mob->fresh()->attendee_limit);
    }

    public function testTheOwnerOfAMobCanNotChangeTheAttendeeLimitBelowTheCurrentAttendeeCount()
    {
        $mob = factory(SocialMob::class)->create(['attendee_limit' => 6]);
        $users = factory(User::class)->times(5)->create();
        $mob->attendees()->attach($users->pluck('id'));

        $newAttendeeLimit = 4;
        $this->assertTrue($newAttendeeLimit < $mob->attendees()->count());
        $this->actingAs($mob->owner)->putJson(route('social_mobs.update', ['social_mob' => $mob->id]), [
            'attendee_limit' => $newAttendeeLimit
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['attendee_limit' => 'The attendee limit must be at least 5.']);


    }

    public function testTheOwnerCanChangeTheDateOfAnUpcomingMob()
    {
        $this->setTestNow('2020-01-01');
        $mob = factory(SocialMob::class)->create(['date' => "2020-01-02"]);
        $newDate = '2020-01-10';

        $this->actingAs($mob->owner)->putJson(route('social_mobs.update', ['social_mob' => $mob->id]), [
            'date' => $newDate,
        ])->assertSuccessful();

        $this->assertEquals($newDate, $mob->fresh()->toArray()['date']);
    }

    public function testTheDateOfTheMobCannotBeSetToThePast()
    {
        $this->setTestNow('2020-01-05');
        $mob = factory(SocialMob::class)->create(['date' => "2020-01-06"]);
        $newDate = '2020-01-03';

        $this->actingAs($mob->owner)->putJson(route('social_mobs.update', ['social_mob' => $mob->id]), [
            'date' => $newDate,
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testTheOwnerCannotUpdateAMobThatAlreadyHappened()
    {
        $this->setTestNow('2020-01-05');
        $mob = factory(SocialMob::class)->create(['date' => "2020-01-01"]);
        $newDate = '2020-01-10';

        $this->actingAs($mob->owner)->putJson(route('social_mobs.update', ['social_mob' => $mob->id]), [
            'date' => $newDate,
        ])->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testAUserThatIsNotAnOwnerOfAMobCannotEditIt()
    {
        $mob = factory(SocialMob::class)->create();
        $notTheOwner = factory(User::class)->create();

        $this->actingAs($notTheOwner)->putJson(route('social_mobs.update', ['social_mob' => $mob->id]), [
            'topic' => 'Anything',
        ])->assertForbidden();
    }

    public function testTheOwnerCanDeleteAnExistingMob()
    {
        $this->withoutExceptionHandling();
        $mob = factory(SocialMob::class)->create();

        $this->actingAs($mob->owner)->deleteJson(route('social_mobs.destroy', ['social_mob' => $mob->id]))
            ->assertSuccessful();

        $this->assertEmpty($mob->fresh());
    }

    public function testAUserThatIsNotAnOwnerOfAMobCannotDeleteIt()
    {
        $mob = factory(SocialMob::class)->create();
        $notTheOwner = factory(User::class)->create();

        $this->actingAs($notTheOwner)->deleteJson(route('social_mobs.destroy', ['social_mob' => $mob->id]))
            ->assertForbidden();
    }

    public function testAGivenUserCanRSVPToASocialMob()
    {
        $existingSocialMob = factory(SocialMob::class)->create();
        $user = factory(User::class)->create();

        $this->actingAs($user)
            ->postJson(route('social_mobs.join', ['social_mob' => $existingSocialMob->id]))
            ->assertSuccessful();

        $this->assertEquals($user->id, $existingSocialMob->attendees->first()->id);
    }

    public function testAUserCannotJoinTheSameMobTwice()
    {
        $existingSocialMob = factory(SocialMob::class)->create();
        $user = factory(User::class)->create();
        $existingSocialMob->attendees()->attach($user);

        $this->actingAs($user)
            ->postJson(route('social_mobs.join', ['social_mob' => $existingSocialMob->id]))
            ->assertForbidden();

        $this->assertCount(1, $existingSocialMob->attendees);
    }

    public function testAUserCanLeaveTheMob()
    {
        $existingSocialMob = factory(SocialMob::class)->create();
        $user = factory(User::class)->create();
        $existingSocialMob->attendees()->attach($user);

        $this->actingAs($user)
            ->postJson(route('social_mobs.leave', ['social_mob' => $existingSocialMob->id]))
            ->assertSuccessful();

        $this->assertEmpty($existingSocialMob->attendees);
    }

    public function testItCanProvideAllSocialMobsOfTheCurrentWeekForAuthenticatedUser()
    {
        $this->withoutExceptionHandling();
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');

        $mondaySocial = factory(SocialMob::class)
            ->create(['date' => $monday, 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $lateWednesdaySocial = factory(SocialMob::class)
            ->create(['date' => $monday->addDays(2), 'start_time' => '04:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $earlyWednesdaySocial = factory(SocialMob::class)
            ->create(['date' => $monday->addDays(2), 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $fridaySocial = factory(SocialMob::class)
            ->create(['date' => $monday->addDays(4), 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        factory(SocialMob::class)
            ->create(['date' => $monday->addDays(8), 'start_time' => '03:30 pm', 'attendee_limit' => 4]); // Socials on another week

        $expectedResponse = [
            $monday->toDateString() => [$mondaySocial],
            $monday->addDays(1)->toDateString() => [],
            $monday->addDays(2)->toDateString() => [$earlyWednesdaySocial, $lateWednesdaySocial],
            $monday->addDays(3)->toDateString() => [],
            $monday->addDays(4)->toDateString() => [$fridaySocial],
        ];

        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->getJson(route('social_mobs.week'));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function testItCanProvideAllSocialMobsOfTheCurrentWeekForAuthenticatedUserEvenOnFridays()
    {
        $this->setTestNow('Next Friday');

        $mondaySocial = factory(SocialMob::class)
            ->create(['date' => Carbon::parse('Last Monday'), 'attendee_limit' => 4])
            ->toArray();
        $fridaySocial = factory(SocialMob::class)
            ->create(['date' => today(), 'attendee_limit' => 4])
            ->toArray();

        $expectedResponse = [
            Carbon::parse('Last Monday')->toDateString() => [$mondaySocial],
            Carbon::parse('Last Tuesday')->toDateString() => [],
            Carbon::parse('Last Wednesday')->toDateString() => [],
            Carbon::parse('Last Thursday')->toDateString() => [],
            today()->toDateString() => [$fridaySocial],
        ];

        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->getJson(route('social_mobs.week'));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function testItCanProvideAllSocialMobsOfASpecifiedWeekForAuthenticatedUserIfADateIsGiven()
    {
        $weekThatHasNoMobs = '2020-05-25';
        $this->setTestNow($weekThatHasNoMobs);
        $weekThatHasTheMobs = '2020-05-04';
        $mondayOfWeekWithMobs = CarbonImmutable::parse($weekThatHasTheMobs);

        $mondaySocial = factory(SocialMob::class)
            ->create(['date' => $mondayOfWeekWithMobs, 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $lateWednesdaySocial = factory(SocialMob::class)
            ->create(['date' => $mondayOfWeekWithMobs->addDays(2), 'start_time' => '04:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $earlyWednesdaySocial = factory(SocialMob::class)
            ->create(['date' => $mondayOfWeekWithMobs->addDays(2), 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $fridaySocial = factory(SocialMob::class)
            ->create(['date' => $mondayOfWeekWithMobs->addDays(4), 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();

        $expectedResponse = [
            $mondayOfWeekWithMobs->toDateString() => [$mondaySocial],
            $mondayOfWeekWithMobs->addDays(1)->toDateString() => [],
            $mondayOfWeekWithMobs->addDays(2)->toDateString() => [$earlyWednesdaySocial, $lateWednesdaySocial],
            $mondayOfWeekWithMobs->addDays(3)->toDateString() => [],
            $mondayOfWeekWithMobs->addDays(4)->toDateString() => [$fridaySocial],
        ];

        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->getJson(route('social_mobs.week', ['date' => $weekThatHasTheMobs]));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function testItDoesNotProvideLocationOfAllSocialMobsOfASpecifiedWeekForAnonymousUser()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        factory(SocialMob::class)->create(['date' => $monday, 'start_time' => '03:30 pm', 'attendee_limit' => 4]);
        factory(SocialMob::class)->create(['date' => $monday->addDays(2), 'start_time' => '04:30 pm', 'attendee_limit' => 4]);
        factory(SocialMob::class)->create(['date' => $monday->addDays(2), 'start_time' => '03:30 pm', 'attendee_limit' => 4]);
        factory(SocialMob::class)->create(['date' => $monday->addDays(4), 'start_time' => '03:30 pm', 'attendee_limit' => 4]);
        factory(SocialMob::class)->create(['date' => $monday->addDays(8), 'start_time' => '03:30 pm', 'attendee_limit' => 4]);

        $response = $this->getJson(route('social_mobs.week'));

        $response->assertSuccessful();
        $response->assertDontSee('At AnyDesk XYZ - abcdefg');
    }

    public function testItDoesNotProvideLocationOfASocialMobForAnonymousUser()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        $mob = factory(SocialMob::class)->create(['date' => $monday, 'start_time' => '03:30 pm']);

        $response = $this->get(route('social_mobs.show', ['social_mob' => $mob]));

        $response->assertSuccessful();
        $response->assertDontSee('At AnyDesk XYZ - abcdefg');
    }

    public function testItCanProvideSocialMobLocationForAuthenticatedUser()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        $socialMob = factory(SocialMob::class)->create(['date' => $monday, 'start_time' => '03:30 pm']);

        $user = factory(User::class)->create();
        $response = $this->actingAs($user)->get(route('social_mobs.show', $socialMob));

        $response->assertSuccessful();
        $response->assertSee('At AnyDesk XYZ - abcdefg');
    }

    public function testItProvidesASummaryOfTheMobsOfTheDay()
    {
        $today = '2020-01-02';
        $tomorrow = '2020-01-03';
        $this->setTestNow($today);
        $user = factory(User::class)->create();

        $todayMobs = factory(SocialMob::class, 2)->create(['date' => $today, 'attendee_limit' => 4]);
        factory(SocialMob::class, 2)->create(['date' => $tomorrow, 'attendee_limit' => 4]);

        $response = $this->actingAs($user)->getJson(route('social_mobs.day'));

        $response->assertJson($todayMobs->toArray());
    }

    public function testTheSlackBotCanSeeTheMobLocation()
    {
        $this->seed();
        $mob = factory(SocialMob::class)->create(['date' => today()]);
        $this->getJson(route('social_mobs.day'), ['Authorization' => 'Bearer '.config('auth.slack_token')])
            ->assertJsonFragment(['location' => $mob->location]);
    }

    public function testAnAttendeeLimitCanBeSetWhenCreatingAtMob()
    {
        $user = factory(User::class)->create();

        $expectedAttendeeLimit = 420;
        $this->actingAs($user)->postJson(
            route('social_mobs.store'),
            $this->defaultParameters(['attendee_limit' => $expectedAttendeeLimit])
        )->assertSuccessful();

        $this->assertEquals($expectedAttendeeLimit, $user->socialMobs->first()->attendee_limit);
    }

    public function testAnAttendeeLimitCannotBeLessThanFour()
    {
        $user = factory(User::class)->create();

        $expectedAttendeeLimit = 3;
        $this->actingAs($user)->postJson(
            route('social_mobs.store'),
            $this->defaultParameters(['attendee_limit' => $expectedAttendeeLimit])
        )->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['attendee_limit' => 'The attendee limit must be at least 4']);
    }

    public function testASocialMobCannotBeJoinedIfTheAttendeeLimitIsMet()
    {
        $user = factory(User::class)->create();
        $attendess = factory(User::class, 4)->create();
        /** @var SocialMob $mob */
        $mob = factory(SocialMob::class)->create(['attendee_limit' => 4]);
        $mob->attendees()->attach($attendess);


        $response = $this->actingAs($user)
            ->postJson(route('social_mobs.join', ['social_mob' => $mob->id]));

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
