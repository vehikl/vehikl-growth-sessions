<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\GrowthSession;
use App\Models\User;
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
        $user = User::factory()->create([
            'is_vehikl_member' => true
        ]);
        $watcher = User::factory()->create([
            'is_vehikl_member' => true
        ]);

        $limitlessSession = GrowthSession::factory()->create(['attendee_limit' => GrowthSession::NO_LIMIT]);
        $limitlessSession->attendees()->attach($user);
        $limitlessSession->watchers()->attach($watcher, ['user_type_id' => 3]);
        $existingComment = Comment::factory()->create(['growth_session_id' => $limitlessSession->id]);

        $response = $this->actingAs($user)
            ->postJson(route('growth_sessions.comments.store', $limitlessSession), ['content' => 'Hello world'])
            ->assertJsonMissing(['attendee_limit' => GrowthSession::NO_LIMIT]);
        $jsonDecoded = json_decode($response->getContent(), true);
        $this->assertEquals($jsonDecoded['attendees'][0]['id'], $user->id);
        $this->assertEquals($jsonDecoded['comments'][1]['content'], 'Hello world');
        $this->assertEquals($jsonDecoded['comments'][0]['content'], $existingComment->content);
        $this->assertEquals($jsonDecoded['watchers'][0]['id'], $watcher->id);
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
        $comments = Comment::factory()->times(4)->create(['growth_session_id' => $growthSession->id]);

        $this->getJson(route('growth_sessions.comments.index', $growthSession))->assertJson($comments->toArray());
    }

    public function testCommentAuthorIncludesIsVehiklMemberFlag()
    {
        $vehiklMember = User::factory()->create(['is_vehikl_member' => true]);
        $nonMember = User::factory()->create(['is_vehikl_member' => false]);
        $growthSession = GrowthSession::factory()->create();
        $memberComment = Comment::factory()->create(['growth_session_id' => $growthSession->id, 'user_id' => $vehiklMember->id]);
        $guestComment = Comment::factory()->create(['growth_session_id' => $growthSession->id, 'user_id' => $nonMember->id]);

        $comments = collect(
            $this->getJson(route('growth_sessions.comments.index', $growthSession))->json()
        );

        $this->assertTrue($comments->firstWhere('id', $memberComment->id)['user']['is_vehikl_member']);
        $this->assertFalse($comments->firstWhere('id', $guestComment->id)['user']['is_vehikl_member']);
    }

    public function testCommentSegmentsRenderImagesOnlyForVehiklMemberAuthors()
    {
        $vehiklMember = User::factory()->create(['is_vehikl_member' => true]);
        $nonMember = User::factory()->create(['is_vehikl_member' => false]);
        $growthSession = GrowthSession::factory()->create();
        $imageUrl = 'https://example.com/funny.gif';
        $memberComment = Comment::factory()->create([
            'growth_session_id' => $growthSession->id,
            'user_id' => $vehiklMember->id,
            'content' => $imageUrl,
        ]);
        $guestComment = Comment::factory()->create([
            'growth_session_id' => $growthSession->id,
            'user_id' => $nonMember->id,
            'content' => $imageUrl,
        ]);

        $comments = collect(
            $this->getJson(route('growth_sessions.comments.index', $growthSession))->json()
        );

        $this->assertEquals(
            [['type' => 'image', 'value' => $imageUrl]],
            $comments->firstWhere('id', $memberComment->id)['segments']
        );
        $this->assertEquals(
            [['type' => 'text', 'value' => $imageUrl]],
            $comments->firstWhere('id', $guestComment->id)['segments']
        );
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
