<?php

namespace Tests\Feature;

use App\SocialMob;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class SocialMobTest extends TestCase
{
    use RefreshDatabase;

    public function testAnAuthenticatedUserCanCreateASocialMob()
    {
        $user = factory(User::class)->create();
        $topic = 'The fundamentals of foo';
        $this->actingAs($user)->postJson(route('social_mob.store'), [
            'topic' => $topic,
            'location' => 'At the central mobbing area',
            'start_time' => now(),
        ])->assertSuccessful();

        $this->assertEquals($topic, $user->socialMobs->first()->topic);
    }

    public function testTheOwnerOfAMobCanEditIt()
    {
        $mob = factory(SocialMob::class)->create();
        $newTopic = 'A brand new topic!';

        $this->actingAs($mob->owner)->putJson(route('social_mob.update', ['social_mob' => $mob->id]), [
            'topic' => $newTopic,
        ])->assertSuccessful();

        $this->assertEquals($newTopic, $mob->fresh()->topic);
    }

    public function testTheOwnerCanChangeTheDateOfAnUpcomingMob()
    {
        Carbon::setTestNow('2020-01-01');
        $startTime = '16:30:00';
        $mob = factory(SocialMob::class)->create(['start_time' => "2020-01-02 $startTime"]);
        $newDate = '2020-01-10';

        $this->actingAs($mob->owner)->putJson(route('social_mob.update', ['social_mob' => $mob->id]), [
            'start_date' => $newDate,
        ])->assertSuccessful();

        $this->assertEquals("{$newDate} $startTime", $mob->fresh()->start_time);
    }

    public function testTheOwnerCannotUpdateAMobThatAlreadyHappened()
    {
        Carbon::setTestNow('2020-01-05');
        $startTime = '16:30:00';
        $mob = factory(SocialMob::class)->create(['start_time' => "2020-01-01 $startTime"]);
        $newDate = '2020-01-10';

        $this->actingAs($mob->owner)->putJson(route('social_mob.update', ['social_mob' => $mob->id]), [
            'start_date' => $newDate,
        ])->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testAUserThatIsNotAnOwnerOfAMobCannotEditIt()
    {
        $mob = factory(SocialMob::class)->create();
        $notTheOwner = factory(User::class)->create();

        $this->actingAs($notTheOwner)->putJson(route('social_mob.update', ['social_mob' => $mob->id]), [
            'topic' => 'Anything'
        ])->assertForbidden();
    }

    public function testTheOwnerCanDeleteAnExistingMob()
    {
        $mob = factory(SocialMob::class)->create();

        $this->actingAs($mob->owner)->deleteJson(route('social_mob.destroy', ['social_mob' => $mob->id]))
            ->assertSuccessful();

        $this->assertEmpty($mob->fresh());
    }

    public function testAUserThatIsNotAnOwnerOfAMobCannotDeleteIt()
    {
        $mob = factory(SocialMob::class)->create();
        $notTheOwner = factory(User::class)->create();

        $this->actingAs($notTheOwner)->deleteJson(route('social_mob.destroy', ['social_mob' => $mob->id]))
            ->assertForbidden();
    }

    public function testAGivenUserCanRSVPToASocialMob()
    {
        $existingSocialMob = factory(SocialMob::class)->create();
        $user = factory(User::class)->create();

        $this->actingAs($user)
            ->postJson(route('social_mob.join', ['social_mob' => $existingSocialMob->id]))
            ->assertSuccessful();

        $this->assertEquals($user->id, $existingSocialMob->attendees->first()->id);
    }

    public function testAUserCannotJoinTheSameMobTwice()
    {
        $existingSocialMob = factory(SocialMob::class)->create();
        $user = factory(User::class)->create();
        $existingSocialMob->attendees()->attach($user);

        $this->actingAs($user)
            ->postJson(route('social_mob.join', ['social_mob' => $existingSocialMob->id]))
            ->assertForbidden();

        $this->assertCount(1, $existingSocialMob->attendees);
    }

    public function testAUSerCanLeaveTheMob()
    {
        $existingSocialMob = factory(SocialMob::class)->create();
        $user = factory(User::class)->create();
        $existingSocialMob->attendees()->attach($user);


        $this->actingAs($user)
            ->postJson(route('social_mob.leave', ['social_mob' => $existingSocialMob->id]))
            ->assertSuccessful();

        $this->assertEmpty($existingSocialMob->attendees);
    }

    public function testItCanProvideAllSocialMobsOfTheCurrentWeek()
    {
        Carbon::setTestNow('First Monday');
        CarbonImmutable::setTestNow('First Monday');

        $mondaySocial = factory(SocialMob::class)
            ->create(['start_time' => now()->toDateTimeString()])
            ->toArray();
        $lateWednesdaySocial = factory(SocialMob::class)
            ->create(['start_time' => now()->addDays(2)->addHours(2)->toDateTimeString()])
            ->toArray();
        $earlyWednesdaySocial = factory(SocialMob::class)
            ->create(['start_time' => now()->addDays(2)->toDateTimeString()])
            ->toArray();
        $fridaySocial = factory(SocialMob::class)
            ->create(['start_time' => now()->addDays(4)->toDateTimeString()])
            ->toArray();
        factory(SocialMob::class)
            ->create(['start_time' => now()->addDays(8)->toDateTimeString()]); // Socials on another week

        $expectedResponse = [
            now()->toDateString() => [$mondaySocial],
            Carbon::parse('First Tuesday')->toDateString() => [],
            Carbon::parse('First Wednesday')->toDateString() => [$earlyWednesdaySocial, $lateWednesdaySocial],
            Carbon::parse('First Thursday')->toDateString() => [],
            Carbon::parse('First Friday')->toDateString() => [$fridaySocial],
        ];

        $response = $this->getJson(route('social_mob.week'));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function testItCanProvideAllSocialMobsOfTheCurrentWeekEvenOnFridays()
    {
        Carbon::setTestNow('Next Friday');
        CarbonImmutable::setTestNow('Next Friday');

        $mondaySocial = factory(SocialMob::class)
            ->create(['start_time' => Carbon::parse('Last Monday')->toDateTimeString()])
            ->toArray();
        $fridaySocial = factory(SocialMob::class)
            ->create(['start_time' => now()->toDateTimeString()])
            ->toArray();

        $expectedResponse = [
            Carbon::parse('Last Monday')->toDateString() => [$mondaySocial],
            Carbon::parse('Last Tuesday')->toDateString() => [],
            Carbon::parse('Last Wednesday')->toDateString() => [],
            Carbon::parse('Last Thursday')->toDateString() => [],
            now()->toDateString() => [$fridaySocial],
        ];

        $response = $this->getJson(route('social_mob.week'));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function testItCanProvideAllSocialMobsOfASpecifiedWeekIfADateIsGiven()
    {
        $weekThatHasNoMobs = '2020-05-25';
        Carbon::setTestNow($weekThatHasNoMobs);
        CarbonImmutable::setTestNow($weekThatHasNoMobs);
        $weekThatHasTheMobs = '2020-05-04';
        $mondayOfWeekWithMobs = CarbonImmutable::parse($weekThatHasTheMobs);

        $mondaySocial = factory(SocialMob::class)
            ->create(['start_time' => $mondayOfWeekWithMobs->toDateTimeString()])
            ->toArray();
        $lateWednesdaySocial = factory(SocialMob::class)
            ->create(['start_time' => $mondayOfWeekWithMobs->addDays(2)->addHours(2)->toDateTimeString()])
            ->toArray();
        $earlyWednesdaySocial = factory(SocialMob::class)
            ->create(['start_time' => $mondayOfWeekWithMobs->addDays(2)->toDateTimeString()])
            ->toArray();
        $fridaySocial = factory(SocialMob::class)
            ->create(['start_time' => $mondayOfWeekWithMobs->addDays(4)->toDateTimeString()])
            ->toArray();

        $expectedResponse = [
            $mondayOfWeekWithMobs->toDateString() => [$mondaySocial],
            $mondayOfWeekWithMobs->addDays(1)->toDateString() => [],
            $mondayOfWeekWithMobs->addDays(2)->toDateString() => [$earlyWednesdaySocial, $lateWednesdaySocial],
            $mondayOfWeekWithMobs->addDays(3)->toDateString() => [],
            $mondayOfWeekWithMobs->addDays(4)->toDateString() => [$fridaySocial],
        ];

        $response = $this->getJson(route('social_mob.week', ['date' => $weekThatHasTheMobs]));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }
}
