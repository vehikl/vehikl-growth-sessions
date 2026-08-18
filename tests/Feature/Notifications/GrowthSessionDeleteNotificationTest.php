<?php

namespace Tests\Feature\Notifications;

use App\Models\GrowthSession;
use App\Models\User;
use App\Models\UserType;
use App\Notifications\GrowthSessionDeletedNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GrowthSessionDeleteNotificationTest extends TestCase
{
    public function test_attendees_and_watchers_are_notified_when_a_session_is_deleted()
    {
        Notification::fake();

        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['title' => 'Weekly Pairing']);
        $attendee = User::factory()->create();
        $watcher = User::factory()->create();
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);
        $growthSession->watchers()->attach($watcher, ['user_type_id' => UserType::WATCHER_ID]);
        $owner = $growthSession->owner;

        $this->actingAs($owner)->deleteJson(route(
            'growth_sessions.destroy',
            ['growth_session' => $growthSession->id]
        ))->assertSuccessful();

        Notification::assertSentTo($attendee, GrowthSessionDeletedNotification::class, function ($notification) {
            return $notification->growthSessionTitle === 'Weekly Pairing';
        });
        Notification::assertSentTo($watcher, GrowthSessionDeletedNotification::class);
        Notification::assertNotSentTo($owner, GrowthSessionDeletedNotification::class);
    }
}
