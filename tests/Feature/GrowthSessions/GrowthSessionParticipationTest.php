<?php

namespace Tests\Feature\GrowthSessions;

use App\GrowthSession;
use App\User;
use App\UserType;
use Illuminate\Http\Response;
use Tests\TestCase;

class GrowthSessionParticipationTest extends TestCase
{
    public function testAGivenUserCanRSVPToAGrowthSession()
    {
        $existingGrowthSession = GrowthSession::factory()->create();
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

    public function testAllowsAUserToWatchAGrowthSession(): void
    {
        $this->withoutExceptionHandling();
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSession = GrowthSession::factory()->create();

        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.watch', $growthSession))
            ->assertSuccessful();

        $this->assertTrue($growthSession->watchers()->first()->is($vehiklMember));
    }

    public function testAllowsAUserToUnwatchAGrowthSession(): void
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
}
