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
}
