<?php

namespace Tests\Feature;

use App\GrowthSession;
use App\User;
use App\UserType;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Tests\TestCase;

class GrowthSessionTest extends TestCase
{
    public function testTheOwnerCanDeleteAnExistingGrowthSession()
    {
        $this->withoutExceptionHandling();
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create();

        $this->actingAs($growthSession->owner)->deleteJson(route(
            'growth_sessions.destroy',
            ['growth_session' => $growthSession->id]
        ))
            ->assertSuccessful();

        $this->assertEmpty($growthSession->fresh());
    }

    public function testAUserThatIsNotAnOwnerOfAGrowthSessionCannotDeleteIt()
    {
        $growthSession = GrowthSession::factory()->create();
        $notTheOwner = User::factory()->create();

        $this->actingAs($notTheOwner)->deleteJson(route(
            'growth_sessions.destroy',
            ['growth_session' => $growthSession->id]
        ))
            ->assertForbidden();
    }

    public function testAGivenUserCanRSVPToAGrowthSession()
    {
        $existingGrowthSession = GrowthSession::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('growth_sessions.join', ['growth_session' => $existingGrowthSession->id]))
            ->assertSuccessful();

        $this->assertEquals($user->id, $existingGrowthSession->attendees->first()->id);
    }

    public function testAUserCannotJoinTheSameGrowthSessionTwice()
    {
        $existingGrowthSession = GrowthSession::factory()->create();
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
        $user = User::factory()->create();
        $existingGrowthSession->attendees()->attach($user);

        $this->actingAs($user)
            ->postJson(route('growth_sessions.leave', ['growth_session' => $existingGrowthSession->id]))
            ->assertSuccessful();

        $this->assertEmpty($existingGrowthSession->attendees);
    }

    public function testAGrowthSessionCannotBeJoinedIfTheAttendeeLimitIsMet()
    {
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

    public function testVehiklUsersCanViewPrivateGrowthSessions()
    {
        $this->setTestNow('2020-01-15');

        $vehiklMember = User::factory()->vehiklMember()->create();
        $monday = CarbonImmutable::parse('Last Monday');
        $vehiklOnlySession = GrowthSession::factory()->create([
            'date' => $monday,
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false
        ]);
        GrowthSession::factory()->create([
            'is_public' => true,
            'date' => $monday->addDays(1),
            'start_time' => '04:30 pm',
            'attendee_limit' => 4
        ]);

        $response = $this->actingAs($vehiklMember)->getJson(route('growth_sessions.week'));

        $response->assertSuccessful()
            ->assertJsonFragment(['id' => $vehiklOnlySession->id]);
    }

    public function testANonMemberUserCannotAccessAPrivateGrowthSession()
    {
        // Create a session
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);

        $user = User::factory()->create(['is_vehikl_member' => false]);

        $this->actingAs($user)
            ->get(route('growth_sessions.show', $growthSession))
            ->assertNotFound();
    }

    public function testAGuestCannotAccessAPrivateGrowthSession()
    {
        // Create a session
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);

        $this->get(route('growth_sessions.show', $growthSession))
            ->assertNotFound();
    }

    public function testAMemberCanAccessAPrivateGrowthSession()
    {
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);

        $user = User::factory()->create(['is_vehikl_member' => true]);

        $this->actingAs($user)
            ->get(route('growth_sessions.show', $growthSession))
            ->assertSuccessful()
            ->assertSee($growthSession->title);
    }

    public function testItProvidesTheGrowthSessionVisibilityInThePayload()
    {
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);

        $user = User::factory()->create(['is_vehikl_member' => true]);

        $response = $this->actingAs($user)
            ->get(route('growth_sessions.week', $growthSession));

        $this->assertArrayHasKey('is_public', $response->json(today()->format("Y-m-d"))[0]);
    }

    private function defaultParameters(array $params = []): array
    {
        return array_merge([
            'topic' => 'The fundamentals of foo',
            'title' => 'Foo',
            'location' => 'At the central mobbing area',
            'start_time' => now()->format('h:i a'),
            'date' => today(),
            'discord_channel' => null,
        ], $params);
    }

    /** @test */
    public function itCanShowGuestForNonVehiklUsers()
    {
        $nonVehiklMember = User::factory()->create();

        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), [], 'attendees')
            ->create();

        $guestMember = $growthSession->attendees()->first();

        $this->actingAs($nonVehiklMember)->get(route('growth_sessions.show', $growthSession))
            ->assertSee('Guest')
            ->assertSee('images\\/guest-avatar.webp')
            ->assertDontSee($guestMember->name)
            ->assertDontSee($growthSession->avatar)
            ->assertDontSee($guestMember->github_nickname);
    }

    /** @test */
    public function itCanShowGuestDetailsForVehiklUsers()
    {
        $vehiklMember = User::factory()->vehiklMember()->create();

        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), [], 'attendees')
            ->create();

        $guestMember = $growthSession->attendees()->first();

        $this->actingAs($vehiklMember)->get(route('growth_sessions.show', $growthSession))
            ->assertSee($guestMember->name)
            ->assertSee($growthSession->avatar)
            ->assertSee($guestMember->github_nickname)
            ->assertDontSee('images\\/guest-avatar.webp')
            ->assertDontSee('Guest');
    }

    /** @test */
    public function itCannotShowGuestDetailsForUnauthenicatedUsers()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), [], 'attendees')
            ->create();

        $guestMember = $growthSession->attendees()->first();

        $this->get(route('growth_sessions.show', $growthSession))
            ->assertSee('Guest')
            ->assertSee('images\\/guest-avatar.webp')
            ->assertDontSee($guestMember->name)
            ->assertDontSee($growthSession->avatar)
            ->assertDontSee($guestMember->github_nickname);
    }

    /** @test */
    public function includesAttendeesInformationEvenForANewlyCreatedGrowthSession(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSessionsAttributes = GrowthSession::factory()->make()->toArray();

        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.store'), $growthSessionsAttributes)
            ->assertJsonFragment([
                'attendees' => []
            ]);
    }

    /** @test */
    public function allowsAUserToWatchAGrowthSession(): void
    {
        $this->withoutExceptionHandling();
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSession = GrowthSession::factory()->create();

        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.watch', $growthSession))
            ->assertSuccessful();

        $this->assertTrue($growthSession->watchers()->first()->is($vehiklMember));
    }

    /** @test */
    public function allowsAUserToUnwatchAGrowthSession(): void
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
