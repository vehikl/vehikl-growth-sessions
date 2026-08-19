<?php

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
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
            return $notification->type === NotificationType::GrowthSessionDateChanged
                && $notification->field === 'date'
                && $notification->old === '2020-01-10'
                && $notification->new === '2020-01-15';
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

    public function test_a_co_owner_is_notified_when_another_owner_makes_the_edit()
    {
        Notification::fake();

        $growthSession = $this->makeSessionWithParticipants(['location' => 'Zoom']);
        $coOwner = User::factory()->create();
        $growthSession->owners()->attach($coOwner, ['user_type_id' => UserType::OWNER_ID]);

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'location' => 'The office',
        ])->assertSuccessful();

        Notification::assertSentTo($coOwner, GrowthSessionUpdatedNotification::class);
    }

    public function test_changing_start_time_sends_a_time_changed_notification()
    {
        Notification::fake();

        $growthSession = $this->makeSessionWithParticipants(['start_time' => '09:00', 'end_time' => '10:00']);
        $attendee = User::factory()->create();
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'start_time' => '11:00',
            'end_time' => '12:00',
        ])->assertSuccessful();

        Notification::assertSentToTimes($attendee, GrowthSessionUpdatedNotification::class, 1);
        Notification::assertSentTo($attendee, GrowthSessionUpdatedNotification::class, function ($notification) {
            return $notification->type === NotificationType::GrowthSessionTimeChanged
                && $notification->field === 'start_time';
        });
    }

    public function test_changing_only_end_time_does_not_send_a_notification()
    {
        Notification::fake();

        $growthSession = $this->makeSessionWithParticipants(['start_time' => '09:00', 'end_time' => '10:00']);
        $attendee = User::factory()->create();
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'end_time' => '12:00',
        ])->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_changing_date_and_location_together_sends_two_distinctly_typed_notifications()
    {
        Notification::fake();

        $growthSession = $this->makeSessionWithParticipants(['date' => '2020-01-10', 'location' => 'Zoom']);
        $attendee = User::factory()->create();
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'date' => '2020-01-15',
            'location' => 'The office',
        ])->assertSuccessful();

        Notification::assertSentToTimes($attendee, GrowthSessionUpdatedNotification::class, 2);
        Notification::assertSentTo($attendee, GrowthSessionUpdatedNotification::class, function ($notification) {
            return $notification->type === NotificationType::GrowthSessionDateChanged;
        });
        Notification::assertSentTo($attendee, GrowthSessionUpdatedNotification::class, function ($notification) {
            return $notification->type === NotificationType::GrowthSessionLocationChanged;
        });
    }

    public function test_the_notification_payload_includes_a_human_readable_value_for_the_change()
    {
        $growthSession = $this->makeSessionWithParticipants();

        $notification = new GrowthSessionUpdatedNotification(
            $growthSession,
            NotificationType::GrowthSessionDateChanged,
            'date',
            '2020-01-10',
            '2020-01-15',
        );

        $this->assertSame(
            ['field' => 'date', 'label' => 'Date', 'value' => 'Jan 15, 2020'],
            $notification->toArray($growthSession->owner)['change']
        );
    }

    public function test_the_notification_payload_formats_time_and_location_values()
    {
        $growthSession = $this->makeSessionWithParticipants();

        $timeNotification = new GrowthSessionUpdatedNotification(
            $growthSession,
            NotificationType::GrowthSessionTimeChanged,
            'start_time',
            '09:00:00',
            '11:00:00',
        );

        $this->assertSame(
            ['field' => 'start_time', 'label' => 'Start time', 'value' => '11:00 AM'],
            $timeNotification->toArray($growthSession->owner)['change']
        );

        $locationNotification = new GrowthSessionUpdatedNotification(
            $growthSession,
            NotificationType::GrowthSessionLocationChanged,
            'location',
            'Zoom',
            'The office',
        );

        $this->assertSame(
            ['field' => 'location', 'label' => 'Location', 'value' => 'The office'],
            $locationNotification->toArray($growthSession->owner)['change']
        );
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
