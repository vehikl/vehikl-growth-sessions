<?php

namespace Tests\Feature;

use App\Comment;
use App\SocialMob as GrowthSession;
use App\User;
use Illuminate\Http\Response;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    public function testAUserCanPostCommentsOnAnExistingGrowthSession()
    {
        $user = User::factory()->create();
        $growthSession = GrowthSession::factory()->create();

        $this->actingAs($user)
            ->postJson(route('social_mobs.comments.store', $growthSession), ['content' => 'Hello world'])
            ->assertSuccessful();

        $this->assertNotEmpty($growthSession->fresh()->comments);
    }

    public function testItDoesNotAllowGuestsToPostComments()
    {
        $growthSession = GrowthSession::factory()->create();

        $this->postJson(route('social_mobs.comments.store', $growthSession), ['content' => 'Hello world'])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testAGuestCanGetAllCommentsOfAMob()
    {
        $socialMob = GrowthSession::factory()->create();
        $comments = Comment::factory()->times(4)->create(['social_mob_id' => $socialMob->id]);

        $this->getJson(route('social_mobs.comments.index', $socialMob))->assertJson($comments->toArray());
    }

    public function testAUserCanDeleteTheirComment()
    {
        $comment = Comment::factory()->create();
        $socialMob = $comment->socialMob;
        $commentOwner = $comment->user;

        $this->actingAs($commentOwner)
            ->deleteJson(route('social_mobs.comments.destroy', [$socialMob, $comment]))
            ->assertSuccessful();

        $this->assertEmpty($comment->fresh());
    }

    public function testAUserCannotDeleteAnotherUsersComment()
    {
        $comment = Comment::factory()->create();
        $socialMob = $comment->socialMob;

        $anotherUser = User::factory()->create();

        $this->actingAs($anotherUser)
            ->deleteJson(route('social_mobs.comments.destroy', [$socialMob, $comment]))
            ->assertForbidden();
    }
}
