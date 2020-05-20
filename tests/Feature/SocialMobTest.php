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

    public function testItCanProvideAllSocialMobsOfTheCurrentWeek()
    {
        Carbon::setTestNow('First Monday of 2020');

        $mondaySocial = factory(SocialMob::class)->create(['start_time' => now()])->toArray();
        $wednesdaySocial = factory(SocialMob::class)->create(['start_time' => now()->addDays(2)])->toArray();
        $anotherWednesdaySocial = factory(SocialMob::class)->create(['start_time' => now()->addDays(2)])->toArray();
        $fridaySocial = factory(SocialMob::class)->create(['start_time' => now()->addDays(4)])->toArray();
        factory(SocialMob::class)->create(['start_time' => now()->addDays(8)]); // Socials on another week

        $expectedSocials = [$mondaySocial, $wednesdaySocial, $anotherWednesdaySocial, $fridaySocial];

        $response = $this->getJson(route('social_mob.index', ['filter' => 'week']));
        $response->assertSuccessful();
        $response->assertJson($expectedSocials);
    }
}
