<?php

namespace Tests\Unit\Support;

use App\Models\GrowthSession;
use App\Models\User;
use App\Models\UserType;
use App\Notifications\PromotedFromTheWaitlistNotification;
use App\Support\Waitlist;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WaitlistTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function testEnrollingPutsTheMemberAtTheBackOfTheQueue()
    {
        $growthSession = $this->fullGrowthSession(1);
        [$first, $second] = User::factory()->times(2)->create()->all();

        Waitlist::for($growthSession)->enrol($first);
        Waitlist::for($growthSession)->enrol($second);

        $this->assertEquals([$first->id, $second->id], $this->queueOf($growthSession));
    }

    public function testAskingForAPlaceAgainMovesNobody()
    {
        $growthSession = $this->fullGrowthSession(1);
        [$first, $second] = User::factory()->times(2)->create()->all();
        $waitlist = Waitlist::for($growthSession);

        $waitlist->enrol($first);
        $waitlist->enrol($second);
        $waitlist->enrol($first);

        $this->assertEquals([$first->id, $second->id], $this->queueOf($growthSession));
    }

    public function testItSaysWhereAMemberStandsAndNothingAboutAnybodyNotInLine()
    {
        $growthSession = $this->fullGrowthSession(1);
        [$first, $second] = User::factory()->times(2)->create()->all();
        $waitlist = Waitlist::for($growthSession);

        $waitlist->enrol($first);
        $waitlist->enrol($second);

        $this->assertEquals(1, $waitlist->positionOf($first));
        $this->assertEquals(2, $waitlist->positionOf($second));
        $this->assertNull($waitlist->positionOf(User::factory()->create()));
        $this->assertNull($waitlist->positionOf(null));
    }

    public function testPromotingFillsEverySeatThatIsFreeAtOnce()
    {
        $growthSession = $this->fullGrowthSession(1);
        [$first, $second, $third] = User::factory()->times(3)->create()->all();
        $waitlist = Waitlist::for($growthSession);

        foreach ([$first, $second, $third] as $hopeful) {
            $waitlist->enrol($hopeful);
        }

        $growthSession->update(['attendee_limit' => 3]);
        $waitlist->promote();

        $this->assertTrue($growthSession->fresh()->hasAttendee($first));
        $this->assertTrue($growthSession->fresh()->hasAttendee($second));
        $this->assertEquals([$third->id], $this->queueOf($growthSession));
    }

    public function testPromotingFromAnEmptyQueueDoesNothing()
    {
        $growthSession = $this->fullGrowthSession(2);
        $growthSession->update(['attendee_limit' => 5]);

        Waitlist::for($growthSession)->promote();

        $this->assertCount(2, $growthSession->fresh()->attendees);
        Notification::assertNothingSent();
    }

    public function testPromotingTwiceOverPromotesNobodyTwice()
    {
        $growthSession = $this->fullGrowthSession(1);
        [$first, $second] = User::factory()->times(2)->create()->all();
        $waitlist = Waitlist::for($growthSession);

        $waitlist->enrol($first);
        $waitlist->enrol($second);
        $growthSession->update(['attendee_limit' => 2]);

        $waitlist->promote();
        $waitlist->promote();

        $this->assertCount(2, $growthSession->fresh()->attendees);
        $this->assertEquals([$second->id], $this->queueOf($growthSession));
        Notification::assertSentToTimes($first, PromotedFromTheWaitlistNotification::class, 1);
    }

    public function testNobodyIsTakenOutOfASeatWhenTheLimitNoLongerFitsThemAll()
    {
        $growthSession = $this->fullGrowthSession(4);
        $hopeful = User::factory()->create();
        Waitlist::for($growthSession)->enrol($hopeful);

        $growthSession->update(['attendee_limit' => 2]);
        Waitlist::for($growthSession)->promote();

        $this->assertCount(4, $growthSession->fresh()->attendees);
        $this->assertEquals([$hopeful->id], $this->queueOf($growthSession));
        Notification::assertNothingSent();
    }

    public function testNobodyIsPromotedIntoAGrowthSessionWhoseDayHasPassed()
    {
        $growthSession = $this->fullGrowthSession(1, today()->subWeek());
        $hopeful = User::factory()->create();
        Waitlist::for($growthSession)->enrol($hopeful);

        $growthSession->update(['attendee_limit' => 4]);
        Waitlist::for($growthSession)->promote();

        $this->assertEquals([$hopeful->id], $this->queueOf($growthSession));
        Notification::assertNothingSent();
    }

    public function testWhoeverIsPromotedIsToldAboutIt()
    {
        $growthSession = $this->fullGrowthSession(1);
        $hopeful = User::factory()->create();
        Waitlist::for($growthSession)->enrol($hopeful);

        $growthSession->update(['attendee_limit' => 2]);
        Waitlist::for($growthSession)->promote();

        Notification::assertSentTo($hopeful, PromotedFromTheWaitlistNotification::class,
            fn (PromotedFromTheWaitlistNotification $notification) => $notification->growthSession->is($growthSession));
    }

    /** @return array<int, int> The user ids in line, front of the queue first. */
    private function queueOf(GrowthSession $growthSession): array
    {
        return $growthSession->fresh()->waitlist->pluck('id')->all();
    }

    private function fullGrowthSession(int $attendeeLimit, $date = null): GrowthSession
    {
        $growthSession = GrowthSession::factory()->create(array_filter([
            'attendee_limit' => $attendeeLimit,
            'date' => $date,
        ]));

        $growthSession->attendees()->attach(
            User::factory()->times($attendeeLimit)->create(),
            ['user_type_id' => UserType::ATTENDEE_ID]
        );

        return $growthSession;
    }
}
