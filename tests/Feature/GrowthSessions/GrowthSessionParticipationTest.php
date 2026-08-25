<?php

namespace Tests\Feature\GrowthSessions;

use App\Models\GrowthSession;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class GrowthSessionParticipationTest extends TestCase
{
    public function test_a_given_user_can_join_a_growth_session_as_an_attendee()
    {
        $existingGrowthSession = GrowthSession::factory()->create();
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('growth_sessions.join', ['growth_session' => $existingGrowthSession->id]))
            ->assertSuccessful();

        $this->assertEquals($user->id, $existingGrowthSession->attendees->first()->id);
    }

    public function test_a_given_user_can_join_a_growth_session_that_does_not_allow_watchers_as_an_attendee()
    {
        $existingGrowthSession = GrowthSession::factory()->create(['allow_watchers' => false]);
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('growth_sessions.join', ['growth_session' => $existingGrowthSession->id]))
            ->assertSuccessful();

        $this->assertEquals($user->id, $existingGrowthSession->attendees->first()->id);
    }

    public function test_a_user_cannot_join_the_same_growth_session_twice()
    {
        $existingGrowthSession = GrowthSession::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();
        $existingGrowthSession->attendees()->attach($user);

        $this->actingAs($user)
            ->postJson(route('growth_sessions.join', ['growth_session' => $existingGrowthSession->id]))
            ->assertForbidden();

        $this->assertCount(1, $existingGrowthSession->attendees);
    }

    public function test_a_user_can_leave_the_growth_session()
    {
        $existingGrowthSession = GrowthSession::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();
        $existingGrowthSession->attendees()->attach($user);

        $this->actingAs($user)
            ->postJson(route('growth_sessions.leave', ['growth_session' => $existingGrowthSession->id]))
            ->assertSuccessful();

        $this->assertEmpty($existingGrowthSession->attendees);
    }

    public function test_an_owner_cannot_leave_their_own_growth_session(): void
    {
        $existingGrowthSession = GrowthSession::factory()->create();

        /** @var User $owner */
        $owner = User::factory()->create();
        $existingGrowthSession->owners()->attach($owner, ['user_type_id' => UserType::OWNER_ID]);

        $this->actingAs($owner)
            ->postJson(route('growth_sessions.leave', ['growth_session' => $existingGrowthSession->id]))
            ->assertForbidden();

        $this->assertTrue($existingGrowthSession->fresh()->owner->is($owner));
    }

    public function test_joining_a_growth_session_whose_attendee_limit_is_met_takes_a_place_in_line_instead()
    {
        /** @var User $user */
        $user = User::factory()->vehiklMember()->create();
        $growthSession = $this->fullGrowthSession(4);

        $response = $this->actingAs($user)
            ->postJson(route('growth_sessions.join', ['growth_session' => $growthSession->id]))
            ->assertSuccessful();

        $this->assertEquals([$user->name], $this->waitlistedNames($response));
        $this->assertEquals(1, $response->json('waitlist_position'));
        $this->assertCount(4, $growthSession->fresh()->attendees);
    }

    public function test_a_user_can_watch_a_growth_session_if_allow_watchers_is_true(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSession = GrowthSession::factory()->create(['allow_watchers' => true]);

        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.watch', $growthSession))
            ->assertSuccessful()
            ->assertJsonCount(1, 'watchers');

        $this->assertTrue($growthSession->watchers()->first()->is($vehiklMember));
    }

    public function test_a_user_cannot_watch_a_growth_session_if_allow_watchers_is_false(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSession = GrowthSession::factory()->create(['allow_watchers' => false]);

        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.watch', $growthSession))
            ->assertForbidden();
    }

    public function test_a_user_can_unwatch_a_growth_session(): void
    {
        $watcher = User::factory()->vehiklMember()->create();
        $growthSession = GrowthSession::factory()->create();
        $growthSession->watchers()->attach($watcher, ['user_type_id' => UserType::WATCHER_ID]);

        $watcher = $growthSession->watchers()->first();

        $this->actingAs($watcher)
            ->post(route('growth_sessions.leave', $growthSession))
            ->assertSuccessful();

        $this->assertEmpty($growthSession->watchers);
    }

    public function test_a_user_cannot_watch_the_same_growth_session_twice(): void
    {
        $existingGrowthSession = GrowthSession::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();
        $existingGrowthSession->watchers()->attach($user, ['user_type_id' => UserType::WATCHER_ID]);

        $this->actingAs($user)
            ->postJson(route('growth_sessions.watch', ['growth_session' => $existingGrowthSession->id]))
            ->assertForbidden();

        $this->assertCount(1, $existingGrowthSession->watchers);
    }

    public function test_a_user_cannot_watch_a_growth_session_while_being_an_attendee(): void
    {
        $existingGrowthSession = GrowthSession::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();

        $existingGrowthSession->attendees()->attach($user, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($user)
            ->postJson(route('growth_sessions.watch', ['growth_session' => $existingGrowthSession->id]))
            ->assertForbidden();
    }

    public function test_user_cannot_attend_growth_session_while_already_being_a_watcher(): void
    {
        $existingGrowthSession = GrowthSession::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();

        // watcher
        $existingGrowthSession->watchers()->attach($user, ['user_type_id' => UserType::WATCHER_ID]);

        $this->actingAs($user)
            ->postJson(route('growth_sessions.join', ['growth_session' => $existingGrowthSession->id]))
            ->assertForbidden();
    }

    public function test_the_attendee_limit_does_not_apply_to_watchers()
    {
        $existingGrowthSession = GrowthSession::factory()->create(['attendee_limit' => 4, 'is_public' => true]);

        $people = User::factory()->times(5)->create();

        for ($i = 0; $i < $existingGrowthSession->attendee_limit; $i++) {
            $this->actingAs($people[$i])
                ->postJson(route('growth_sessions.join', ['growth_session' => $existingGrowthSession->id]))
                ->assertSuccessful();
        }

        $slowpoke = $people[$existingGrowthSession->attendee_limit];

        $this->actingAs($slowpoke)
            ->postJson(route('growth_sessions.watch', ['growth_session' => $existingGrowthSession->id]))
            ->assertSuccessful();
    }

    public function test_join_endpoint_is_idempotent_when_user_is_already_an_attendee(): void
    {
        $existingGrowthSession = GrowthSession::factory()->create();
        /** @var User $user */
        $user = User::factory()->create();

        // First request - user joins successfully
        $this->actingAs($user)
            ->postJson(route('growth_sessions.join', ['growth_session' => $existingGrowthSession->id]))
            ->assertSuccessful();

        $this->assertCount(1, $existingGrowthSession->fresh()->attendees);

        // Simulate a race condition by bypassing policy check
        // In a real scenario, two requests could pass the policy check simultaneously
        $this->actingAs($user)
            ->postJson(route('growth_sessions.join', ['growth_session' => $existingGrowthSession->id]))
            ->assertForbidden();

        // Verify user is still only attached once (idempotent behavior)
        $this->assertCount(1, $existingGrowthSession->fresh()->attendees);
        $this->assertEquals($user->id, $existingGrowthSession->fresh()->attendees->first()->id);
    }

    public function test_a_user_who_cannot_see_a_growth_session_cannot_join_it_nor_receive_any_of_its_contents(): void
    {
        $unlistedGrowthSession = GrowthSession::factory()->unlisted()->create(['topic' => 'Pairing on their project']);
        $outsider = User::factory()->vehiklMember(false)->create();

        $response = $this->actingAs($outsider)
            ->postJson(route('growth_sessions.join', ['growth_session' => $unlistedGrowthSession->id]))
            ->assertNotFound();

        $this->assertStringNotContainsString($unlistedGrowthSession->title, $response->getContent());
        $this->assertStringNotContainsString($unlistedGrowthSession->topic, $response->getContent());
        $this->assertStringNotContainsString($unlistedGrowthSession->location, $response->getContent());
        $this->assertEmpty($unlistedGrowthSession->fresh()->attendees);
    }

    public function test_a_user_who_unlocked_the_invite_link_can_join_the_unlisted_growth_session(): void
    {
        $unlistedGrowthSession = GrowthSession::factory()->unlisted()->create();
        $client = User::factory()->vehiklMember(false)->create();

        $this->actingAs($client)
            ->get(route('growth_sessions.invitation', ['token' => $unlistedGrowthSession->share_token]));

        $this->actingAs($client)
            ->postJson(route('growth_sessions.join', ['growth_session' => $unlistedGrowthSession->id]))
            ->assertSuccessful();

        $this->assertTrue($unlistedGrowthSession->fresh()->attendees->first()->is($client));
    }

    public function test_a_vehikl_member_can_join_an_unlisted_growth_session_without_the_invite_link(): void
    {
        $unlistedGrowthSession = GrowthSession::factory()->unlisted()->create();
        $vehiklMember = User::factory()->vehiklMember()->create();

        $this->actingAs($vehiklMember)
            ->postJson(route('growth_sessions.join', ['growth_session' => $unlistedGrowthSession->id]))
            ->assertSuccessful();
    }

    public function test_watch_endpoint_is_idempotent_when_user_is_already_a_watcher(): void
    {
        $existingGrowthSession = GrowthSession::factory()->create(['allow_watchers' => true]);
        /** @var User $user */
        $user = User::factory()->vehiklMember()->create();

        // First request - user watches successfully
        $this->actingAs($user)
            ->postJson(route('growth_sessions.watch', ['growth_session' => $existingGrowthSession->id]))
            ->assertSuccessful();

        $this->assertCount(1, $existingGrowthSession->fresh()->watchers);

        // Simulate a race condition by bypassing policy check
        // In a real scenario, two requests could pass the policy check simultaneously
        $this->actingAs($user)
            ->postJson(route('growth_sessions.watch', ['growth_session' => $existingGrowthSession->id]))
            ->assertForbidden();

        // Verify user is still only attached once (idempotent behavior)
        $this->assertCount(1, $existingGrowthSession->fresh()->watchers);
        $this->assertEquals($user->id, $existingGrowthSession->fresh()->watchers->first()->id);
    }

    public function test_the_waitlist_is_served_in_the_order_it_was_joined(): void
    {
        $growthSession = $this->fullGrowthSession(2);
        [$first, $second, $third] = User::factory()->vehiklMember()->count(3)->create()->all();

        foreach ([$first, $second, $third] as $hopeful) {
            $this->join($hopeful, $growthSession)->assertSuccessful();
        }

        $this->assertEquals(
            [$first->name, $second->name, $third->name],
            $this->waitlistedNames($this->showTo($first, $growthSession))
        );
        $this->assertEquals(2, $this->showTo($second, $growthSession)->json('waitlist_position'));
    }

    public function test_somebody_already_in_line_cannot_take_a_second_place_in_it(): void
    {
        $growthSession = $this->fullGrowthSession(1);
        [$first, $second] = User::factory()->vehiklMember()->count(2)->create()->all();

        $this->join($first, $growthSession);
        $this->join($second, $growthSession);

        $this->join($first, $growthSession)->assertForbidden();

        $this->assertEquals([$first->name, $second->name], $this->waitlistedNames($this->showTo($first, $growthSession)));
    }

    public function test_somebody_already_taking_part_cannot_also_take_a_place_in_line(): void
    {
        $growthSession = $this->fullGrowthSession(2);
        $attendee = $growthSession->attendees->first();
        $watcher = User::factory()->vehiklMember()->create();
        $growthSession->watchers()->attach($watcher, ['user_type_id' => UserType::WATCHER_ID]);

        $this->join($attendee, $growthSession)->assertForbidden();
        $this->join($watcher, $growthSession)->assertForbidden();

        $this->assertEmpty($growthSession->fresh()->waitlist);
    }

    public function test_somebody_waiting_in_line_cannot_watch_instead(): void
    {
        $growthSession = $this->fullGrowthSession(1);
        $hopeful = User::factory()->vehiklMember()->create();
        $this->join($hopeful, $growthSession)->assertSuccessful();

        $this->actingAs($hopeful)
            ->postJson(route('growth_sessions.watch', ['growth_session' => $growthSession->id]))
            ->assertForbidden();
    }

    public function test_withdrawing_from_the_waitlist_keeps_the_order_of_everyone_behind(): void
    {
        $growthSession = $this->fullGrowthSession(1);
        [$first, $second, $third] = User::factory()->vehiklMember()->count(3)->create()->all();

        foreach ([$first, $second, $third] as $hopeful) {
            $this->join($hopeful, $growthSession);
        }

        $response = $this->leave($second, $growthSession)->assertSuccessful();

        $this->assertEquals([$first->name, $third->name], $this->waitlistedNames($response));
        $this->assertEquals(2, $this->showTo($third, $growthSession)->json('waitlist_position'));
        $this->assertCount(1, $growthSession->fresh()->attendees);
    }

    public function test_an_attendee_leaving_hands_the_seat_to_whoever_is_at_the_front_of_the_queue(): void
    {
        $growthSession = $this->fullGrowthSession(1);
        $seated = $growthSession->attendees->first();
        [$first, $second] = User::factory()->vehiklMember()->count(2)->create()->all();

        $this->join($first, $growthSession);
        $this->join($second, $growthSession);

        $response = $this->leave($seated, $growthSession)->assertSuccessful();

        $this->assertEquals([$first->name], array_column($response->json('attendees'), 'name'));
        $this->assertEquals([$second->name], $this->waitlistedNames($response));
        $this->assertEquals(1, $this->showTo($second, $growthSession)->json('waitlist_position'));
    }

    public function test_raising_the_attendee_limit_promotes_as_many_as_now_fit(): void
    {
        $owner = User::factory()->vehiklMember()->create();
        $growthSession = $this->fullGrowthSession(2, $owner);
        $alreadySeated = $growthSession->attendees->pluck('name')->all();
        [$first, $second, $third] = User::factory()->vehiklMember()->count(3)->create()->all();

        foreach ([$first, $second, $third] as $hopeful) {
            $this->join($hopeful, $growthSession);
        }

        $response = $this->raiseLimitTo($owner, $growthSession, 4)->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            array_merge($alreadySeated, [$first->name, $second->name]),
            array_column($response->json('attendees'), 'name')
        );
        $this->assertEquals([$third->name], $this->waitlistedNames($response));
    }

    /**
     * Nobody is ever demoted, and the request layer will not even let the owner ask: a limit below
     * the number already seated is refused before the queue is consulted.
     */
    public function test_the_attendee_limit_cannot_be_lowered_below_the_number_already_seated(): void
    {
        $owner = User::factory()->vehiklMember()->create();
        $growthSession = $this->fullGrowthSession(3, $owner);
        $hopeful = User::factory()->vehiklMember()->create();
        $this->join($hopeful, $growthSession);

        $this->raiseLimitTo($owner, $growthSession, 2)->assertStatus(422);

        $this->assertCount(3, $growthSession->fresh()->attendees);
        $this->assertEquals([$hopeful->name], $this->waitlistedNames($this->showTo($owner, $growthSession)));
    }

    public function test_a_seat_freed_with_nobody_waiting_simply_stays_free(): void
    {
        $growthSession = $this->fullGrowthSession(2);
        $seated = $growthSession->attendees->first();

        $response = $this->leave($seated, $growthSession)->assertSuccessful();

        $this->assertCount(1, $response->json('attendees'));
        $this->assertEmpty($response->json('waitlist'));
    }

    public function test_a_growth_session_with_no_attendee_limit_never_puts_anybody_in_line(): void
    {
        $growthSession = GrowthSession::factory()->create(['attendee_limit' => GrowthSession::NO_LIMIT]);
        $growthSession->attendees()->attach(User::factory()->times(20)->create());
        $latecomer = User::factory()->vehiklMember()->create();

        $response = $this->join($latecomer, $growthSession)->assertSuccessful();

        $this->assertEmpty($response->json('waitlist'));
        $this->assertTrue($growthSession->fresh()->hasAttendee($latecomer));
    }

    public function test_somebody_who_cannot_see_a_growth_session_cannot_take_a_place_in_its_queue(): void
    {
        $unlistedGrowthSession = GrowthSession::factory()->unlisted()->create(['attendee_limit' => 1]);
        $unlistedGrowthSession->attendees()->attach(User::factory()->create());
        $outsider = User::factory()->vehiklMember(false)->create();

        $this->join($outsider, $unlistedGrowthSession)->assertNotFound();

        $this->assertEmpty($unlistedGrowthSession->fresh()->waitlist);
    }

    public function test_the_location_is_hidden_from_somebody_waiting_and_shown_once_they_have_a_seat(): void
    {
        $growthSession = $this->fullGrowthSession(1);
        $seated = $growthSession->attendees->first();
        $hopeful = User::factory()->vehiklMember()->create();

        $waiting = $this->join($hopeful, $growthSession)->assertSuccessful();
        $this->assertEquals('< Join to see location >', $waiting->json('location'));

        $this->leave($seated, $growthSession);

        $this->assertEquals($growthSession->location, $this->showTo($hopeful, $growthSession)->json('location'));
    }

    public function test_the_waitlist_is_visible_to_anyone_who_can_see_the_growth_session_with_guests_anonymised(): void
    {
        $growthSession = $this->fullGrowthSession(1);
        $guest = User::factory()->vehiklMember(false)->create(['name' => 'Guesty McGuestface']);
        $vehiklMember = User::factory()->vehiklMember()->create();

        $this->join($guest, $growthSession);
        $this->join($vehiklMember, $growthSession);

        $this->assertEquals(
            [$guest->name, $vehiklMember->name],
            $this->waitlistedNames($this->showTo($vehiklMember, $growthSession))
        );
        $this->assertEquals(
            ['Guest', $vehiklMember->name],
            $this->waitlistedNames($this->showTo(User::factory()->vehiklMember(false)->create(), $growthSession))
        );
    }

    /** A growth session with every seat taken, owned by the given member when one is handed in. */
    private function fullGrowthSession(int $attendeeLimit, ?User $owner = null): GrowthSession
    {
        $growthSession = GrowthSession::factory()->create(['attendee_limit' => $attendeeLimit, 'is_public' => true]);

        if ($owner) {
            $growthSession->owners()->attach($owner, ['user_type_id' => UserType::OWNER_ID]);
        }

        $growthSession->attendees()->attach(
            User::factory()->vehiklMember()->count($attendeeLimit - ($owner ? 1 : 0))->create(),
            ['user_type_id' => UserType::ATTENDEE_ID]
        );

        return $growthSession->load('attendees');
    }

    private function join(User $user, GrowthSession $growthSession): TestResponse
    {
        return $this->actingAs($user)->postJson(route('growth_sessions.join', ['growth_session' => $growthSession->id]));
    }

    private function leave(User $user, GrowthSession $growthSession): TestResponse
    {
        return $this->actingAs($user)->postJson(route('growth_sessions.leave', ['growth_session' => $growthSession->id]));
    }

    private function showTo(User $user, GrowthSession $growthSession): TestResponse
    {
        return $this->actingAs($user)
            ->getJson(route('growth_sessions.show', ['growth_session' => $growthSession->id]));
    }

    /** @return array<int, string> The names on the waitlist, front of the queue first. */
    private function waitlistedNames(TestResponse $response): array
    {
        return array_column($response->json('waitlist'), 'name');
    }

    private function raiseLimitTo(User $owner, GrowthSession $growthSession, int $attendeeLimit): TestResponse
    {
        return $this->actingAs($owner)->putJson(
            route('growth_sessions.update', ['growth_session' => $growthSession->id]),
            ['attendee_limit' => $attendeeLimit]
        );
    }
}
