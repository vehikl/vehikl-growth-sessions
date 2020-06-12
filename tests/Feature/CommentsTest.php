<?php

namespace Tests\Feature;

use App\SocialMob;
use App\User;
use Illuminate\Http\Response;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    public function testAUserCanPostCommentsOnAnExistingMob()
    {
        $user = factory(User::class)->create();
        $socialMob = factory(SocialMob::class)->create();

        $this->actingAs($user)
            ->postJson(route('social_mobs.comments.store', $socialMob), ['content' => 'Hello world'])
            ->assertSuccessful();

        $this->assertNotEmpty($socialMob->fresh()->comments);
    }

    public function testItDoesNotAllowGuestsToPostComments()
    {
        $socialMob = factory(SocialMob::class)->create();

        $this->postJson(route('social_mobs.comments.store', $socialMob), ['content' => 'Hello world'])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
