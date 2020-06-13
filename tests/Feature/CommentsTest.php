<?php

namespace Tests\Feature;

use App\Comment;
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

    public function testAGuestCanGetAllCommentsOfAMob()
    {
        $socialMob = factory(SocialMob::class)->create();
        $comments = factory(Comment::class, 4)->create(['social_mob_id' => $socialMob->id]);

        $this->getJson(route('social_mobs.comments.index', $socialMob))->assertJson($comments->toArray())->dump();
    }
}
