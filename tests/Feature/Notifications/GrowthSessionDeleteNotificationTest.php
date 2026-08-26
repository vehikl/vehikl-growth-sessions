<?php

namespace Tests\Feature\Notifications;

use App\Enums\Role;
use App\Models\GrowthSession;
use App\Models\User;
use App\Notifications\GrowthSessionDeletedNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GrowthSessionDeleteNotificationTest extends TestCase
{
    public function test_attendees_and_watchers_are_notified_when_a_session_is_deleted()
    {
        Notification::fake();

        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => Role::Owner->value], 'owners')
            ->create(['title' => 'Weekly Pairing']);
        $attendee = User::factory()->create();
        $watcher = User::factory()->create();
        $growthSession->attendees()->attach($attendee, ['user_type_id' => Role::Attendee->value]);
        $growthSession->watchers()->attach($watcher, ['user_type_id' => Role::Watcher->value]);
        $owner = $growthSession->owner;

        $this->actingAs($owner)->deleteJson(route(
            'growth_sessions.destroy',
            ['growth_session' => $growthSession->id]
        ))->assertSuccessful();

        Notification::assertSentTo($attendee, GrowthSessionDeletedNotification::class, function ($notification) {
            return $notification->title === 'Weekly Pairing';
        });
        Notification::assertSentTo($watcher, GrowthSessionDeletedNotification::class);
        Notification::assertNotSentTo($owner, GrowthSessionDeletedNotification::class);
    }

    public function test_the_notification_data_carries_the_sessions_date()
    {
        $this->setTestNow('2020-08-01');
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => Role::Owner->value], 'owners')
            ->create(['title' => 'Weekly Pairing', 'date' => '2020-08-20']);
        $attendee = User::factory()->create();
        $growthSession->attendees()->attach($attendee, ['user_type_id' => Role::Attendee->value]);

        $this->actingAs($growthSession->owner)->deleteJson(route(
            'growth_sessions.destroy',
            ['growth_session' => $growthSession->id]
        ))->assertSuccessful();

        $data = $attendee->notifications()->first()->data;

        $this->assertSame('2020-08-20', $data['date']);
    }
}
