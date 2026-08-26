<?php

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Enums\Role;
use App\Models\GrowthSession;
use App\Models\User;
use App\Notifications\PromotedFromTheWaitlistNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WaitlistPromotionNotificationTest extends TestCase
{
    public function test_it_is_delivered_to_the_notification_feed_and_broadcast()
    {
        $notification = new PromotedFromTheWaitlistNotification($this->growthSession());

        $this->assertEquals(['database', 'broadcast'], $notification->via(User::factory()->create()));
    }

    public function test_it_carries_the_growth_session_a_seat_came_free_in()
    {
        $growthSession = $this->growthSession();

        $payload = (new PromotedFromTheWaitlistNotification($growthSession))->toArray();

        $this->assertEquals([
            'type' => NotificationType::GrowthSessionWaitlistPromotion->value,
            'growth_session_id' => $growthSession->id,
            'title' => $growthSession->title,
            'date' => $growthSession->date->format('Y-m-d'),
            'url' => "/growth_sessions/{$growthSession->id}",
        ], $payload);
    }

    public function test_the_bell_can_tell_it_apart_from_every_other_notification()
    {
        $notification = new PromotedFromTheWaitlistNotification($this->growthSession());

        $this->assertEquals('growth_session_waitlist_promotion', $notification->broadcastType());
    }

    public function test_it_lands_in_the_feed_of_whoever_takes_the_freed_seat()
    {
        $growthSession = GrowthSession::factory()->create(['attendee_limit' => 1]);
        $seated = User::factory()->create();
        $hopeful = User::factory()->create();
        $growthSession->attendees()->attach($seated, ['user_type_id' => Role::Attendee->value]);

        $this->actingAs($hopeful)->postJson(route('growth_sessions.join', ['growth_session' => $growthSession->id]))
            ->assertSuccessful();

        Notification::fake();

        $this->actingAs($seated)->postJson(route('growth_sessions.leave', ['growth_session' => $growthSession->id]))
            ->assertSuccessful();

        Notification::assertSentTo(
            $hopeful,
            PromotedFromTheWaitlistNotification::class,
            fn (PromotedFromTheWaitlistNotification $notification) => $notification->growthSession->is($growthSession)
        );
    }

    public function test_nobody_else_hears_about_a_promotion_that_is_not_theirs()
    {
        Notification::fake();

        $growthSession = GrowthSession::factory()->create(['attendee_limit' => 1]);
        $seated = User::factory()->create();
        $growthSession->attendees()->attach($seated, ['user_type_id' => Role::Attendee->value]);

        $this->actingAs($seated)->postJson(route('growth_sessions.leave', ['growth_session' => $growthSession->id]))
            ->assertSuccessful();

        Notification::assertNotSentTo($seated, PromotedFromTheWaitlistNotification::class);
    }

    private function growthSession(): GrowthSession
    {
        return GrowthSession::factory()->create();
    }
}
