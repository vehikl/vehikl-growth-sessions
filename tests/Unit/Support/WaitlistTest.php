<?php

namespace Tests\Unit\Support;

use App\Models\GrowthSession;
use App\Models\User;
use App\Models\UserType;
use App\Support\Seating;
use App\Support\Waitlist;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The read side of the queue. Taking a place in one and coming off it belong to {@see Seating}, and
 * are covered by {@see SeatingTest}.
 */
class WaitlistTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_it_lists_the_members_in_line_front_of_the_queue_first()
    {
        $growthSession = $this->fullGrowthSession();
        [$first, $second] = User::factory()->count(2)->create()->all();
        Seating::for($growthSession)->take($first);
        Seating::for($growthSession)->take($second);

        $members = Waitlist::for($growthSession->fresh())->members();

        $this->assertEquals([$first->id, $second->id], $members->pluck('id')->all());
    }

    public function test_it_says_where_a_member_stands_and_nothing_about_anybody_not_in_line()
    {
        $growthSession = $this->fullGrowthSession();
        [$first, $second] = User::factory()->count(2)->create()->all();
        Seating::for($growthSession)->take($first);
        Seating::for($growthSession)->take($second);
        $waitlist = Waitlist::for($growthSession->fresh());

        $this->assertEquals(1, $waitlist->positionOf($first));
        $this->assertEquals(2, $waitlist->positionOf($second));
        $this->assertNull($waitlist->positionOf(User::factory()->create()));
        $this->assertNull($waitlist->positionOf(null));
    }

    private function fullGrowthSession(): GrowthSession
    {
        $growthSession = GrowthSession::factory()->create(['attendee_limit' => 1]);

        $growthSession->attendees()->attach(
            User::factory()->create(),
            ['user_type_id' => UserType::ATTENDEE_ID]
        );

        return $growthSession;
    }
}
