<?php

namespace Tests\Feature;

use App\Http\Resources\Comment as CommentResource;
use App\Models\Comment;
use App\Models\GrowthSession;
use App\Models\User;
use Illuminate\Http\Response;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    public function test_a_user_can_post_comments_on_an_existing_growth_session()
    {
        $user = User::factory()->create();
        $growthSession = GrowthSession::factory()->create();
        $this->actingAs($user)
            ->postJson(route('growth_sessions.comments.store', $growthSession), ['content' => 'Hello world'])
            ->assertSuccessful();

        $this->assertNotEmpty($growthSession->fresh()->comments);
    }

    public function test_it_returns_growth_session_resource_on_comment_submission()
    {
        $user = User::factory()->create([
            'is_vehikl_member' => true,
        ]);
        $watcher = User::factory()->create([
            'is_vehikl_member' => true,
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
        $this->assertEquals($jsonDecoded['comments'][0]['content'], 'Hello world');
        $this->assertEquals($jsonDecoded['comments'][1]['content'], $existingComment->content);
        $this->assertEquals($jsonDecoded['watchers'][0]['id'], $watcher->id);
    }

    public function test_it_returns_growth_session_resource_on_comment_destroy()
    {
        GrowthSession::factory()
            ->has(Comment::factory())
            ->create(['attendee_limit' => GrowthSession::NO_LIMIT]);

        $targetComment = Comment::query()->first();

        $this->actingAs($targetComment->user)
            ->deleteJson(
                route('growth_sessions.comments.destroy', [
                    'growth_session' => $targetComment->growthSession,
                    'comment' => $targetComment,
                ]))
            ->assertJsonMissing(['attendee_limit' => GrowthSession::NO_LIMIT]);
    }

    public function test_it_does_not_allow_guests_to_post_comments()
    {
        $growthSession = GrowthSession::factory()->create();

        $this->postJson(route('growth_sessions.comments.store', $growthSession), ['content' => 'Hello world'])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_a_guest_can_get_all_comments_of_a_growth_session()
    {
        $growthSession = GrowthSession::factory()->create();
        $comments = Comment::factory()->times(4)->create(['growth_session_id' => $growthSession->id]);

        $expectedResponse = json_decode(json_encode(CommentResource::collection($comments->sortByDesc('id')->values())->toArray(request())), true);

        $this->getJson(route('growth_sessions.comments.index', $growthSession))->assertJson($expectedResponse);
    }

    public function test_comment_author_includes_is_vehikl_member_flag()
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

    public function test_comment_segments_render_images_only_for_vehikl_member_authors()
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

    public function test_comment_segments_withhold_links_as_well_as_images_for_non_members()
    {
        $nonMember = User::factory()->create(['is_vehikl_member' => false]);
        $growthSession = GrowthSession::factory()->create();
        $imageUrl = 'https://example.com/funny.gif';
        $linkUrl = 'https://example.com/info';
        $content = "photo {$imageUrl} and info {$linkUrl}";
        $comment = Comment::factory()->create([
            'growth_session_id' => $growthSession->id,
            'user_id' => $nonMember->id,
            'content' => $content,
        ]);

        $comments = collect(
            $this->getJson(route('growth_sessions.comments.index', $growthSession))->json()
        );

        $this->assertEquals(
            [['type' => 'text', 'value' => $content]],
            $comments->firstWhere('id', $comment->id)['segments']
        );
    }

    public function test_comment_segments_still_render_links_for_vehikl_member_authors()
    {
        $vehiklMember = User::factory()->create(['is_vehikl_member' => true]);
        $growthSession = GrowthSession::factory()->create();
        $linkUrl = 'https://example.com/info';
        $comment = Comment::factory()->create([
            'growth_session_id' => $growthSession->id,
            'user_id' => $vehiklMember->id,
            'content' => "info {$linkUrl}",
        ]);

        $comments = collect(
            $this->getJson(route('growth_sessions.comments.index', $growthSession))->json()
        );

        $this->assertEquals([
            ['type' => 'text', 'value' => 'info '],
            ['type' => 'link', 'value' => $linkUrl, 'opens_in_new_tab' => true],
        ], $comments->firstWhere('id', $comment->id)['segments']);
    }

    /** Pins the exact field set of the comment API contract, including the nested user. */
    public function test_the_comment_payload_contains_exactly_the_expected_fields()
    {
        $growthSession = GrowthSession::factory()->create();
        Comment::factory()->create(['growth_session_id' => $growthSession->id]);

        $response = $this->getJson(route('growth_sessions.comments.index', $growthSession))
            ->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            ['id', 'growth_session_id', 'content', 'segments', 'time_stamp', 'user'],
            array_keys($response->json('0'))
        );
        $this->assertEqualsCanonicalizing(
            ['id', 'github_nickname', 'name', 'avatar', 'is_vehikl_member'],
            array_keys($response->json('0.user'))
        );
    }

    public function test_a_user_can_delete_their_comment()
    {
        $comment = Comment::factory()->create();
        $growthSession = $comment->growthSession;
        $commentOwner = $comment->user;

        $this->actingAs($commentOwner)
            ->deleteJson(route('growth_sessions.comments.destroy', [$growthSession, $comment]))
            ->assertSuccessful();

        $this->assertEmpty($comment->fresh());
    }

    public function test_a_user_cannot_delete_another_users_comment()
    {
        $comment = Comment::factory()->create();
        $growthSession = $comment->growthSession;

        $anotherUser = User::factory()->create();

        $this->actingAs($anotherUser)
            ->deleteJson(route('growth_sessions.comments.destroy', [$growthSession, $comment]))
            ->assertForbidden();
    }
}
