<?php

namespace Tests\Feature;

use App\GrowthSession;
use App\User;
use Tests\TestCase;

class GrowthSessionTest extends TestCase
{
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
}
