<?php

namespace Tests\Feature\GrowthSessions;

use App\GrowthSession;
use App\User;
use App\UserType;
use Illuminate\Http\Response;
use Tests\TestCase;

class GrowthSessionParticipationTest extends TestCase
{
    public function testAGivenUserCanJoinAGrowthSessionAsAnAttendee()
    {
        $existingGrowthSession = GrowthSession::factory()->create();
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('growth_sessions.join', ['growth_session' => $existingGrowthSession->id]))
            ->assertSuccessful();

        $this->assertEquals($user->id, $existingGrowthSession->attendees->first()->id);
    }

    public function testAGivenUserCanJoinAGrowthSessionThatDoesNotAllowWatchersAsAnAttendee()
    {
        $existingGrowthSession = GrowthSession::factory()->create(['allow_watchers' => false]);
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('growth_sessions.join', ['growth_session' => $existingGrowthSession->id]))
            ->assertSuccessful();

        $this->assertEquals($user->id, $existingGrowthSession->attendees->first()->id);
    }

    public function testAUserCannotJoinTheSameGrowthSessionTwice()
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

    public function testAUserCanLeaveTheGrowthSession()
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

    public function testAGrowthSessionCannotBeJoinedIfTheAttendeeLimitIsMet()
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

    public function testAUserCanWatchAGrowthSessionIfAllowWatchersIsTrue(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSession = GrowthSession::factory()->create(['allow_watchers' => true]);

        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.watch', $growthSession))
            ->assertSuccessful()
            ->assertJsonCount(1, 'watchers');

        $this->assertTrue($growthSession->watchers()->first()->is($vehiklMember));
    }

    public function testAUserCannotWatchAGrowthSessionIfAllowWatchersIsFalse(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSession = GrowthSession::factory()->create(['allow_watchers' => false]);

        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.watch', $growthSession))
            ->assertForbidden();
    }

    public function testAUserCanUnwatchAGrowthSession(): void
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

    public function testAUserCannotWatchTheSameGrowthSessionTwice(): void
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

    public function testAUserCannotWatchAGrowthSessionWhileBeingAnAttendee(): void
    {
        $existingGrowthSession = GrowthSession::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();

        $existingGrowthSession->attendees()->attach($user, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($user)
            ->postJson(route('growth_sessions.watch', ['growth_session' => $existingGrowthSession->id]))
            ->assertForbidden();
    }

    public function testUserCannotAttendGrowthSessionWhileAlreadyBeingAWatcher(): void
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

    public function testTheAttendeeLimitDoesNotApplyToWatchers()
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
}
