<?php

namespace Tests\Feature;

use App\Comment;
use App\GrowthSession;
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
            ->postJson(route('growth_sessions.comments.store', $growthSession), ['content' => 'Hello world'])
            ->assertSuccessful();

        $this->assertNotEmpty($growthSession->fresh()->comments);
    }

    public function testItReturnsGrowthSessionResourceOnCommentSubmission()
    {
        $user = User::factory()->create();
        $limitlessSession = GrowthSession::factory()->create(['attendee_limit' => GrowthSession::NO_LIMIT]);

        $this->actingAs($user)
            ->postJson(route('growth_sessions.comments.store', $limitlessSession), ['content' => 'Hello world'])
            ->assertJsonMissing(['attendee_limit' => GrowthSession::NO_LIMIT]);
    }

    public function testItReturnsGrowthSessionResourceOnCommentDestroy()
    {
        GrowthSession::factory()
            ->has(Comment::factory())
            ->create(['attendee_limit' => GrowthSession::NO_LIMIT]);

        $targetComment = Comment::query()->first();

        $this->actingAs($targetComment->user)
            ->deleteJson(
                route('growth_sessions.comments.destroy', [
                        'growth_session' => $targetComment->growthSession,
                        'comment' => $targetComment
                    ]))
            ->assertJsonMissing(['attendee_limit' => GrowthSession::NO_LIMIT]);
    }

    public function testItDoesNotAllowGuestsToPostComments()
    {
        $growthSession = GrowthSession::factory()->create();

        $this->postJson(route('growth_sessions.comments.store', $growthSession), ['content' => 'Hello world'])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testAGuestCanGetAllCommentsOfAGrowthSession()
    {
        $growthSession = GrowthSession::factory()->create();
        $comments = Comment::factory()->times(4)->create(['social_mob_id' => $growthSession->id]);

        $this->getJson(route('growth_sessions.comments.index', $growthSession))->assertJson($comments->toArray());
    }

    public function testAUserCanDeleteTheirComment()
    {
        $comment = Comment::factory()->create();
        $growthSession = $comment->growthSession;
        $commentOwner = $comment->user;

        $this->actingAs($commentOwner)
            ->deleteJson(route('growth_sessions.comments.destroy', [$growthSession, $comment]))
            ->assertSuccessful();

        $this->assertEmpty($comment->fresh());
    }

    public function testAUserCannotDeleteAnotherUsersComment()
    {
        $comment = Comment::factory()->create();
        $growthSession = $comment->growthSession;

        $anotherUser = User::factory()->create();

        $this->actingAs($anotherUser)
            ->deleteJson(route('growth_sessions.comments.destroy', [$growthSession, $comment]))
            ->assertForbidden();
    }
}
