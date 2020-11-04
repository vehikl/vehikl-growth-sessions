<?php

namespace Tests\Feature;

use App\SocialMob;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityControllerTest extends TestCase
{
    use RefreshDatabase;


    public function testItReturnsATallyOfMentoredBy()
    {
        $this->withoutExceptionHandling();
        // replace this with a user factory
        $me = User::factory()->create();

        $mobA = SocialMob::factory()->create();
        $mobB = SocialMob::factory()->create();
        $mobC = SocialMob::factory()->create(['owner_id' => $mobB->owner_id]);

        $mobA->attendees()->attach($me);
        $mobB->attendees()->attach($me);
        $mobC->attendees()->attach($me);

        $expectedPeers = [
            [
                'count' => 1,
                'user' => $mobA->owner->toArray(),
            ],
            [
                'count' => 2,
                'user' => $mobB->owner->toArray(),
            ],
        ];

        $this->actingAs($me)
             ->get(route('activity'))
             ->assertViewHas('peers', $expectedPeers);
    }
}
