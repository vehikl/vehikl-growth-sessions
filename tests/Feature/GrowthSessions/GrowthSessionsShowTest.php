<?php

namespace Tests\Feature\GrowthSessions;

use App\GrowthSession;
use App\User;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class GrowthSessionsShowTest extends TestCase
{
    public function testTheGetGrowthSessionEndpointReturnsAViewIfTheRequestIsNotExpectingJson()
    {
        $growthSession = GrowthSession::factory()->create();
        $this->get(route('growth_sessions.show', $growthSession))
            ->assertViewHas(['growthSession.id' => $growthSession->id]);
    }

    public function testTheGetGrowthSessionEndpointReturnsAJsonPayloadIfTheRequestIsExpectingJson()
    {
        $growthSession = GrowthSession::factory()->create();
        $this->getJson(route('growth_sessions.show', $growthSession))
            ->assertJsonFragment(['id' => $growthSession->id]);
    }

    public function testItDoesNotProvideLocationOfAGrowthSessionForAnonymousUser()
    {
        $this->withoutExceptionHandling();
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        $growthSession = GrowthSession::factory()->create(['date' => $monday, 'start_time' => '03:30 pm']);

        $response = $this->get(route('growth_sessions.show', ['growth_session' => $growthSession]));

        $response->assertSuccessful();
        $response->assertDontSee('At AnyDesk XYZ - abcdefg');
    }

    public function testItCanProvideGrowthSessionLocationForAuthenticatedUser()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        $growthSession = GrowthSession::factory()->create(['date' => $monday, 'start_time' => '03:30 pm']);

        /** @var User $user */
        $user = User::factory()->create(['is_vehikl_member' => false]);
        $response = $this->actingAs($user)->get(route('growth_sessions.show', $growthSession));

        $response->assertSuccessful();
        $response->assertSee('At AnyDesk XYZ - abcdefg');
    }

    public function testTheSlackBotCanSeeTheGrowthSessionLocation()
    {
        $this->seed();
        $growthSession = GrowthSession::factory()->create(['date' => today()]);
        $this->getJson(route('growth_sessions.day'), ['Authorization' => 'Bearer ' . config('auth.slack_token')])
            ->assertJsonFragment(['location' => $growthSession->location]);
    }

    public function testANonMemberUserCannotAccessAPrivateGrowthSession()
    {
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);

        /** @var User $user */
        $user = User::factory()->create(['is_vehikl_member' => false]);

        $this->actingAs($user)
            ->get(route('growth_sessions.show', $growthSession))
            ->assertNotFound();
    }

    public function testAGuestCannotAccessAPrivateGrowthSession()
    {
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);

        $this->get(route('growth_sessions.show', $growthSession))
            ->assertNotFound();
    }

    public function testAMemberCanAccessAPrivateGrowthSession()
    {
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);
        /** @var User $user */
        $user = User::factory()->create(['is_vehikl_member' => true]);

        $this->actingAs($user)
            ->get(route('growth_sessions.show', $growthSession))
            ->assertSuccessful()
            ->assertSee($growthSession->title);
    }

    public function testItProvidesTheGrowthSessionVisibilityInThePayload()
    {
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);
        /** @var User $user */
        $user = User::factory()->create(['is_vehikl_member' => true]);

        $response = $this->actingAs($user)
            ->get(route('growth_sessions.week', $growthSession));

        $this->assertArrayHasKey('is_public', $response->json(today()->format("Y-m-d"))[0]);
    }

    /** @test */
    public function itCanShowGuestForNonVehiklUsers()
    {
        /** @var User $nonVehiklMember */
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
}
