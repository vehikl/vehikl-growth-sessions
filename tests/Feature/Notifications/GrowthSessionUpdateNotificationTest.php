<?php

namespace Tests\Feature\Notifications;

use App\Models\GrowthSession;
use App\Models\User;
use App\Models\UserType;
use App\Notifications\GrowthSessionUpdatedNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GrowthSessionUpdateNotificationTest extends TestCase
{
    private function makeSessionWithParticipants(array $attributes = []): GrowthSession
    {
        return GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create($attributes);
    }

    public function test_attendees_are_notified_when_a_meaningful_field_changes()
    {
        Notification::fake();

        $this->setTestNow('2020-01-01');
        $growthSession = $this->makeSessionWithParticipants(['date' => '2020-01-10']);
        $attendee = User::factory()->create();
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'date' => '2020-01-15',
        ])->assertSuccessful();

        Notification::assertSentTo($attendee, GrowthSessionUpdatedNotification::class, function ($notification) {
            return array_key_exists('date', $notification->changes)
                && $notification->changes['date']['old'] === '2020-01-10'
                && $notification->changes['date']['new'] === '2020-01-15';
        });
    }

    public function test_watchers_are_notified_when_a_meaningful_field_changes()
    {
        Notification::fake();

        $growthSession = $this->makeSessionWithParticipants(['location' => 'Zoom']);
        $watcher = User::factory()->create();
        $growthSession->watchers()->attach($watcher, ['user_type_id' => UserType::WATCHER_ID]);

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'location' => 'The office',
        ])->assertSuccessful();

        Notification::assertSentTo($watcher, GrowthSessionUpdatedNotification::class);
    }

    public function test_the_owner_is_not_notified_of_their_own_edit()
    {
        Notification::fake();

        $growthSession = $this->makeSessionWithParticipants(['location' => 'Zoom']);

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'location' => 'The office',
        ])->assertSuccessful();

        Notification::assertNotSentTo($growthSession->owner, GrowthSessionUpdatedNotification::class);
    }

    public function test_no_notification_is_sent_when_only_non_meaningful_fields_change()
    {
        Notification::fake();

        $growthSession = $this->makeSessionWithParticipants(['title' => 'Old title']);
        $attendee = User::factory()->create();
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'title' => 'New title',
        ])->assertSuccessful();

        Notification::assertNothingSent();
    }
}
