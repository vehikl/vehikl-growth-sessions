<?php

namespace Tests\Feature\GrowthSessions;

use App\Events\GrowthSessionModified;
use App\Models\GrowthSession;
use App\Models\GrowthSessionUser;
use App\Models\User;
use App\Models\UserType;
use App\Support\Seating;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
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

    public function testTakingAPlaceInLineBroadcastsAWaitlistChange(): void
    {
        $growthSession = $this->fullGrowthSession();

        Seating::for($growthSession)->take(User::factory()->create());

        Event::assertDispatched(GrowthSessionModified::class, function (GrowthSessionModified $event) use ($growthSession) {
            return $event->growthSessionId === $growthSession->id
                && $event->action === GrowthSessionModified::ACTION_UPDATED
                && $event->type === GrowthSessionModified::TYPE_WAITLIST;
        });
    }

    /**
     * A promotion rewrites a row that is already there rather than adding or removing one, so this
     * is the only thing telling the board that the roster moved.
     */
    public function testAPromotionOffTheWaitlistBroadcastsAnAttendeeChange(): void
    {
        Notification::fake();
        $growthSession = $this->fullGrowthSession();
        Seating::for($growthSession)->take(User::factory()->create());
        $growthSession->update(['attendee_limit' => 2]);
        Event::fake([GrowthSessionModified::class]);

        Seating::for($growthSession)->reseat();

        Event::assertDispatched(GrowthSessionModified::class, function (GrowthSessionModified $event) use ($growthSession) {
            return $event->growthSessionId === $growthSession->id
                && $event->action === GrowthSessionModified::ACTION_UPDATED
                && $event->type === GrowthSessionModified::TYPE_ATTENDEES;
        });
    }

    private function fullGrowthSession(): GrowthSession
    {
        $growthSession = GrowthSession::factory()->create(['attendee_limit' => 1]);
        $growthSession->attendees()->attach(User::factory()->create(), ['user_type_id' => UserType::ATTENDEE_ID]);

        return $growthSession;
    }
}
