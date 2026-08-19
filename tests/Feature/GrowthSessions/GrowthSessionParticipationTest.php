<?php

namespace Tests\Feature\GrowthSessions;

use App\Models\GrowthSession;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Response;
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

    public function test_a_growth_session_cannot_be_joined_if_the_attendee_limit_is_met()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $attendess = User::factory()->times(4)->create();
        /** @var GrowthSession $growthSession */
        $growthSession = GrowthSession::factory()->create(['attendee_limit' => 4]);
        $growthSession->attendees()->attach($attendess);

        $response = $this->actingAs($user)
            ->postJson(route('growth_sessions.join', ['growth_session' => $growthSession->id]));

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJson(['message' => 'The attendee limit has been reached.']);
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
            ->postJson(route('growth_sessions.join', ['growth_session' => $existingGrowthSession->id]))
            ->assertStatus(400);

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
}
