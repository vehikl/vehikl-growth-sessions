<?php

namespace Tests\Feature;

use App\SocialMob;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->withoutExceptionHandling();
        Carbon::setTestNow('First Monday');

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
}
