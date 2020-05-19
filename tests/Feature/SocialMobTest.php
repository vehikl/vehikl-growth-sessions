<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialMobTest extends TestCase
{
    use RefreshDatabase;

    public function testAnAuthenticatedUserCanCreateASocialMob()
    {
        $user = factory(User::class)->create();
        $topic = 'The fundamentals of foo';
        $this->actingAs($user)->postJson(route('social-mob.store'), [
           'topic' => $topic,
           'start_time' => now(),
        ])->assertSuccessful();

        $this->assertEquals($topic, $user->socialMobs->first()->topic);
    }
}
