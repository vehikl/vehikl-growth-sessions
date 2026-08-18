<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\GrowthSessionDeletedNotification;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    public function test_it_lists_the_authenticated_users_recent_notifications_with_an_unread_count()
    {
        $user = User::factory()->create();
        $user->notify(new GrowthSessionDeletedNotification('Weekly Pairing'));
        $user->notify(new GrowthSessionDeletedNotification('Lightning Talks'));
        $user->notifications()->first()->markAsRead();

        $response = $this->actingAs($user)->getJson(route('notifications.index'))->assertSuccessful();

        $response->assertJsonCount(2, 'data');
        $response->assertJson(['unread_count' => 1]);
    }

    public function test_it_only_returns_notifications_belonging_to_the_authenticated_user()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherUser->notify(new GrowthSessionDeletedNotification('Not yours'));

        $response = $this->actingAs($user)->getJson(route('notifications.index'))->assertSuccessful();

        $response->assertJsonCount(0, 'data');
    }

    public function test_marking_notifications_as_read_clears_the_unread_count()
    {
        $user = User::factory()->create();
        $user->notify(new GrowthSessionDeletedNotification('Weekly Pairing'));
        $user->notify(new GrowthSessionDeletedNotification('Lightning Talks'));

        $this->actingAs($user)->postJson(route('notifications.read'))->assertNoContent();

        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
