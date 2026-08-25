<?php

namespace Tests\Unit\Support;

use App\Models\GrowthSession;
use App\Models\User;
use App\Models\UserType;
use App\Notifications\PromotedFromTheWaitlistNotification;
use App\Support\Seating;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SeatingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /**
     * Promotion reads relations off pivot rows it has just fetched, and every one of those reads is
     * a lazy load waiting to happen. Only while lazy loading is prevented does a missing eager load
     * fail here rather than in a browser, so the rest of this file is worth less without it.
     *
     * Worth knowing alongside this: Eloquent arms the guard on a hydrated model only when its query
     * returned more than one row (see Builder::hydrate()). A roster with a single row proves
     * nothing about eager loading, which is why the tests below keep at least two.
     */
    public function test_these_tests_run_with_lazy_loading_prevented()
    {
        $this->assertTrue(Model::preventsLazyLoading());
    }

    public function test_asking_takes_a_seat_while_one_is_free()
    {
        $growthSession = $this->growthSessionSeating(4);
        $hopeful = User::factory()->create();

        Seating::for($growthSession)->take($hopeful);

        $this->assertTrue($growthSession->fresh()->hasAttendee($hopeful));
        $this->assertEquals([], $this->queueOf($growthSession));
    }

    public function test_asking_takes_a_place_in_line_once_the_seats_are_gone()
    {
        $growthSession = $this->fullGrowthSession(1);
        $hopeful = User::factory()->create();

        Seating::for($growthSession)->take($hopeful);

        $this->assertFalse($growthSession->fresh()->hasAttendee($hopeful));
        $this->assertEquals([$hopeful->id], $this->queueOf($growthSession));
    }

    /**
     * The seat-or-queue decision is made here, under the lock, rather than by the caller - so two
     * members going after the same last seat cannot both be told it was free.
     */
    public function test_two_members_going_after_the_same_last_seat_get_one_seat_between_them()
    {
        $growthSession = GrowthSession::factory()->create(['attendee_limit' => 1]);
        [$first, $second] = User::factory()->count(2)->create()->all();
        $seating = Seating::for($growthSession);

        $seating->take($first);
        $seating->take($second);

        $this->assertCount(1, $growthSession->fresh()->attendees);
        $this->assertTrue($growthSession->fresh()->hasAttendee($first));
        $this->assertEquals([$second->id], $this->queueOf($growthSession));
    }

    public function test_asking_puts_the_member_at_the_back_of_the_queue()
    {
        $growthSession = $this->fullGrowthSession(1);
        [$first, $second] = User::factory()->count(2)->create()->all();

        Seating::for($growthSession)->take($first);
        Seating::for($growthSession)->take($second);

        $this->assertEquals([$first->id, $second->id], $this->queueOf($growthSession));
    }

    public function test_asking_for_a_place_again_moves_nobody()
    {
        $growthSession = $this->fullGrowthSession(1);
        [$first, $second] = User::factory()->count(2)->create()->all();
        $seating = Seating::for($growthSession);

        $seating->take($first);
        $seating->take($second);
        $seating->take($first);

        $this->assertEquals([$first->id, $second->id], $this->queueOf($growthSession));
    }

    public function test_spectating_costs_no_seat_and_displaces_nobody()
    {
        $growthSession = $this->fullGrowthSession(1);
        $hopeful = User::factory()->create();
        Seating::for($growthSession)->take($hopeful);
        $spectator = User::factory()->create();

        Seating::for($growthSession)->spectate($spectator);

        $this->assertTrue($growthSession->fresh()->hasWatcher($spectator));
        $this->assertCount(1, $growthSession->fresh()->attendees);
        $this->assertEquals([$hopeful->id], $this->queueOf($growthSession));
    }

    public function test_reseating_fills_every_seat_that_is_free_at_once()
    {
        $growthSession = $this->fullGrowthSession(1);
        [$first, $second, $third] = User::factory()->count(3)->create()->all();
        $seating = Seating::for($growthSession);

        foreach ([$first, $second, $third] as $hopeful) {
            $seating->take($hopeful);
        }

        $growthSession->update(['attendee_limit' => 3]);
        $seating->reseat();

        $this->assertTrue($growthSession->fresh()->hasAttendee($first));
        $this->assertTrue($growthSession->fresh()->hasAttendee($second));
        $this->assertEquals([$third->id], $this->queueOf($growthSession));
    }

    public function test_reseating_from_an_empty_queue_does_nothing()
    {
        $growthSession = $this->fullGrowthSession(2);
        $growthSession->update(['attendee_limit' => 5]);

        Seating::for($growthSession)->reseat();

        $this->assertCount(2, $growthSession->fresh()->attendees);
        Notification::assertNothingSent();
    }

    public function test_reseating_twice_over_promotes_nobody_twice()
    {
        $growthSession = $this->fullGrowthSession(1);
        [$first, $second] = User::factory()->count(2)->create()->all();
        $seating = Seating::for($growthSession);

        $seating->take($first);
        $seating->take($second);
        $growthSession->update(['attendee_limit' => 2]);

        $seating->reseat();
        $seating->reseat();

        $this->assertCount(2, $growthSession->fresh()->attendees);
        $this->assertEquals([$second->id], $this->queueOf($growthSession));
        Notification::assertSentToTimes($first, PromotedFromTheWaitlistNotification::class, 1);
    }

    public function test_nobody_is_taken_out_of_a_seat_when_the_limit_no_longer_fits_them_all()
    {
        $growthSession = $this->fullGrowthSession(4);
        $hopeful = User::factory()->create();
        Seating::for($growthSession)->take($hopeful);

        $growthSession->update(['attendee_limit' => 2]);
        Seating::for($growthSession)->reseat();

        $this->assertCount(4, $growthSession->fresh()->attendees);
        $this->assertEquals([$hopeful->id], $this->queueOf($growthSession));
        Notification::assertNothingSent();
    }

    public function test_nobody_is_promoted_into_a_growth_session_whose_day_has_passed()
    {
        $growthSession = $this->fullGrowthSession(1, today()->subWeek());
        $hopeful = User::factory()->create();
        Seating::for($growthSession)->take($hopeful);

        $growthSession->update(['attendee_limit' => 4]);
        Seating::for($growthSession)->reseat();

        $this->assertEquals([$hopeful->id], $this->queueOf($growthSession));
        Notification::assertNothingSent();
    }

    public function test_whoever_is_promoted_is_told_about_it()
    {
        $growthSession = $this->fullGrowthSession(1);
        $hopeful = User::factory()->create();
        Seating::for($growthSession)->take($hopeful);

        $growthSession->update(['attendee_limit' => 2]);
        Seating::for($growthSession)->reseat();

        Notification::assertSentTo($hopeful, PromotedFromTheWaitlistNotification::class,
            fn (PromotedFromTheWaitlistNotification $notification) => $notification->growthSession->is($growthSession));
    }

    public function test_giving_up_a_seat_hands_it_to_the_front_of_the_queue()
    {
        $growthSession = $this->fullGrowthSession(2);
        $seated = $growthSession->attendees->first();
        [$first, $second] = User::factory()->count(2)->create()->all();
        $seating = Seating::for($growthSession);
        $seating->take($first);
        $seating->take($second);

        $seating->release($seated);

        $this->assertFalse($growthSession->fresh()->hasAttendee($seated));
        $this->assertTrue($growthSession->fresh()->hasAttendee($first));
        $this->assertEquals([$second->id], $this->queueOf($growthSession));
        Notification::assertSentTo($first, PromotedFromTheWaitlistNotification::class);
    }

    public function test_giving_up_a_place_in_line_frees_no_seat_and_moves_nobody_up_into_one()
    {
        $growthSession = $this->fullGrowthSession(1);
        [$first, $second, $third] = User::factory()->count(3)->create()->all();
        $seating = Seating::for($growthSession);

        foreach ([$first, $second, $third] as $hopeful) {
            $seating->take($hopeful);
        }

        $seating->release($second);

        $this->assertCount(1, $growthSession->fresh()->attendees);
        $this->assertEquals([$first->id, $third->id], $this->queueOf($growthSession));
        Notification::assertNothingSent();
    }

    public function test_giving_up_a_seat_at_a_growth_session_whose_day_has_passed_promotes_nobody()
    {
        $growthSession = $this->fullGrowthSession(1, today()->subWeek());
        $seated = $growthSession->attendees->first();
        $hopeful = User::factory()->create();
        Seating::for($growthSession)->take($hopeful);

        Seating::for($growthSession)->release($seated);

        $this->assertCount(0, $growthSession->fresh()->attendees);
        $this->assertEquals([$hopeful->id], $this->queueOf($growthSession));
        Notification::assertNothingSent();
    }

    /** @return array<int, int> The user ids in line, front of the queue first. */
    private function queueOf(GrowthSession $growthSession): array
    {
        return $growthSession->fresh()->waitlist->pluck('id')->all();
    }

    /** A growth session seated to its limit, so the next member to ask takes a place in line. */
    private function fullGrowthSession(int $attendeeLimit, $date = null): GrowthSession
    {
        $growthSession = $this->growthSessionSeating($attendeeLimit, $date);

        $growthSession->attendees()->attach(
            User::factory()->count($attendeeLimit)->create(),
            ['user_type_id' => UserType::ATTENDEE_ID]
        );

        return $growthSession;
    }

    private function growthSessionSeating(int $attendeeLimit, $date = null): GrowthSession
    {
        return GrowthSession::factory()->create(array_filter([
            'attendee_limit' => $attendeeLimit,
            'date' => $date,
        ]));
    }
}
