<?php

namespace Tests\Feature\GrowthSessions;

use App\Events\GrowthSessionModified;
use App\Models\GrowthSession;
use App\Models\GrowthSessionUser;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GrowthSessionBroadcastTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([GrowthSessionModified::class]);
    }

    public function testAddingAWatcherBroadcastsAWatcherChange(): void
    {
        $growthSession = GrowthSession::factory()->create();
        $user = User::factory()->create();

        $pivot = new GrowthSessionUser;
        $pivot->growth_session_id = $growthSession->id;
        $pivot->user_id = $user->id;
        $pivot->user_type_id = UserType::WATCHER_ID;
        $pivot->save();

        Event::assertDispatched(GrowthSessionModified::class, function (GrowthSessionModified $event) use ($growthSession) {
            return $event->growthSessionId === $growthSession->id
                && $event->action === GrowthSessionModified::ACTION_UPDATED
                && $event->type === GrowthSessionModified::TYPE_WATCHERS;
        });
    }

    public function testAddingAnAttendeeBroadcastsAnAttendeeChange(): void
    {
        $growthSession = GrowthSession::factory()->create();
        $user = User::factory()->create();

        $pivot = new GrowthSessionUser;
        $pivot->growth_session_id = $growthSession->id;
        $pivot->user_id = $user->id;
        $pivot->user_type_id = UserType::ATTENDEE_ID;
        $pivot->save();

        Event::assertDispatched(GrowthSessionModified::class, function (GrowthSessionModified $event) use ($growthSession) {
            return $event->growthSessionId === $growthSession->id
                && $event->action === GrowthSessionModified::ACTION_UPDATED
                && $event->type === GrowthSessionModified::TYPE_ATTENDEES;
        });
    }

    /**
     * The tests above save the pivot themselves, which fires the observer whatever the endpoints
     * do. These go through the endpoints, which is where a seat can quietly change hands without
     * anyone being told.
     */
    public function testJoiningThroughTheEndpointBroadcastsAnAttendeeChange(): void
    {
        $growthSession = $this->futureGrowthSessionOwnedBySomeone();
        $joiner = User::factory()->create(['is_vehikl_member' => true]);

        $this->actingAs($joiner)->postJson(route('growth_sessions.join', $growthSession))->assertSuccessful();

        Event::assertDispatched(GrowthSessionModified::class, function (GrowthSessionModified $event) use ($growthSession) {
            return $event->growthSessionId === $growthSession->id
                && $event->type === GrowthSessionModified::TYPE_ATTENDEES;
        });
    }

    public function testLeavingThroughTheEndpointBroadcastsAnAttendeeChange(): void
    {
        $growthSession = $this->futureGrowthSessionOwnedBySomeone();
        $attendee = User::factory()->create(['is_vehikl_member' => true]);
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($attendee)->postJson(route('growth_sessions.leave', $growthSession))->assertSuccessful();

        Event::assertDispatched(GrowthSessionModified::class, function (GrowthSessionModified $event) use ($growthSession) {
            return $event->growthSessionId === $growthSession->id
                && $event->action === GrowthSessionModified::ACTION_DELETED
                && $event->type === GrowthSessionModified::TYPE_ATTENDEES;
        });
    }

    public function testAWatcherLeavingThroughTheEndpointBroadcastsAWatcherChange(): void
    {
        $growthSession = $this->futureGrowthSessionOwnedBySomeone();
        $watcher = User::factory()->create(['is_vehikl_member' => true]);
        $growthSession->watchers()->attach($watcher, ['user_type_id' => UserType::WATCHER_ID]);

        $this->actingAs($watcher)->postJson(route('growth_sessions.leave', $growthSession))->assertSuccessful();

        Event::assertDispatched(GrowthSessionModified::class, function (GrowthSessionModified $event) use ($growthSession) {
            return $event->growthSessionId === $growthSession->id
                && $event->type === GrowthSessionModified::TYPE_WATCHERS;
        });
    }

    private function futureGrowthSessionOwnedBySomeone(): GrowthSession
    {
        $growthSession = GrowthSession::factory()->create([
            'date' => now()->addDays(3)->format('Y-m-d'),
            'attendee_limit' => 4,
            'allow_watchers' => true,
        ]);
        $growthSession->attendees()->attach(User::factory()->create(), ['user_type_id' => UserType::OWNER_ID]);

        return $growthSession;
    }
}
