<?php

namespace Tests\Feature\Notifications;

use App\Models\Comment;
use App\Models\GrowthSession;
use App\Models\User;
use App\Models\UserType;
use App\Notifications\GrowthSessionCommentAddedNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GrowthSessionCommentNotificationTest extends TestCase
{
    private function makeSessionWithParticipants(): GrowthSession
    {
        return GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create();
    }

    public function test_attendees_and_watchers_are_notified_when_a_comment_is_added()
    {
        Notification::fake();

        $growthSession = $this->makeSessionWithParticipants();
        $attendee = User::factory()->create();
        $watcher = User::factory()->create();
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);
        $growthSession->watchers()->attach($watcher, ['user_type_id' => UserType::WATCHER_ID]);

        $this->actingAs($growthSession->owner)->postJson(route(
            'growth_sessions.comments.store',
            ['growth_session' => $growthSession->id]
        ), [
            'content' => 'Great session!',
        ])->assertSuccessful();

        Notification::assertSentTo($attendee, GrowthSessionCommentAddedNotification::class);
        Notification::assertSentTo($watcher, GrowthSessionCommentAddedNotification::class);
    }

    public function test_the_owner_is_notified_when_someone_else_comments()
    {
        Notification::fake();

        $growthSession = $this->makeSessionWithParticipants();
        $attendee = User::factory()->create();
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($attendee)->postJson(route(
            'growth_sessions.comments.store',
            ['growth_session' => $growthSession->id]
        ), [
            'content' => 'Great session!',
        ])->assertSuccessful();

        Notification::assertSentTo($growthSession->owner, GrowthSessionCommentAddedNotification::class);
    }

    public function test_the_commenter_is_not_notified_of_their_own_comment()
    {
        Notification::fake();

        $growthSession = $this->makeSessionWithParticipants();
        $attendee = User::factory()->create();
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($attendee)->postJson(route(
            'growth_sessions.comments.store',
            ['growth_session' => $growthSession->id]
        ), [
            'content' => 'Great session!',
        ])->assertSuccessful();

        Notification::assertNotSentTo($attendee, GrowthSessionCommentAddedNotification::class);
    }

    public function test_the_owner_is_not_notified_when_they_comment_on_their_own_session()
    {
        Notification::fake();

        $growthSession = $this->makeSessionWithParticipants();
        $attendee = User::factory()->create();
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($growthSession->owner)->postJson(route(
            'growth_sessions.comments.store',
            ['growth_session' => $growthSession->id]
        ), [
            'content' => 'Great session!',
        ])->assertSuccessful();

        Notification::assertNotSentTo($growthSession->owner, GrowthSessionCommentAddedNotification::class);
        Notification::assertSentTo($attendee, GrowthSessionCommentAddedNotification::class);
    }

    public function test_the_notification_payload_includes_the_commenters_id_and_avatar()
    {
        $growthSession = $this->makeSessionWithParticipants();
        $commenter = User::factory()->create(['avatar' => 'https://example.com/avatar.jpg']);
        $comment = Comment::factory()->create([
            'user_id' => $commenter->id,
            'growth_session_id' => $growthSession->id,
        ]);

        $payload = (new GrowthSessionCommentAddedNotification($comment))->toArray($growthSession->owner);

        $this->assertSame($commenter->id, $payload['commenter_id']);
        $this->assertSame('https://example.com/avatar.jpg', $payload['commenter_avatar']);
    }
}
