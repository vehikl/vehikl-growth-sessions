<?php

namespace Tests\Feature\GrowthSessionProposals;

use App\Models\GrowthSessionProposal;
use App\Models\User;
use Tests\TestCase;

class GrowthSessionProposalDeleteTest extends TestCase
{
    public function testCreatorCanDeleteTheirProposal(): void
    {
        $user = User::factory()->create(['is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->create(['creator_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson(route('proposals.destroy', $proposal));

        $response->assertSuccessful();
        $this->assertDatabaseMissing('growth_session_proposals', ['id' => $proposal->id]);
    }

    public function testVehiklMembersCanDeleteAnyProposal(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $nonVehiklUser = User::factory()->create(['is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->create(['creator_id' => $nonVehiklUser->id]);

        $response = $this->actingAs($vehiklMember)->deleteJson(route('proposals.destroy', $proposal));

        $response->assertSuccessful();
        $this->assertDatabaseMissing('growth_session_proposals', ['id' => $proposal->id]);
    }

    public function testNonCreatorNonVehiklMemberCannotDeleteProposal(): void
    {
        $user1 = User::factory()->create(['is_vehikl_member' => false]);
        $user2 = User::factory()->create(['is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->create(['creator_id' => $user1->id]);

        $response = $this->actingAs($user2)->deleteJson(route('proposals.destroy', $proposal));

        $response->assertForbidden();
        $this->assertDatabaseHas('growth_session_proposals', ['id' => $proposal->id]);
    }

    public function testDeletingProposalCascadeDeletesTimePreferences(): void
    {
        $user = User::factory()->create();
        $proposal = GrowthSessionProposal::factory()->create(['creator_id' => $user->id]);

        \App\Models\GrowthSessionProposalTimePreference::factory()->count(3)->create([
            'growth_session_proposal_id' => $proposal->id,
        ]);

        $this->assertDatabaseCount('growth_session_proposal_time_preferences', 3);

        $response = $this->actingAs($user)->deleteJson(route('proposals.destroy', $proposal));

        $response->assertSuccessful();
        $this->assertDatabaseCount('growth_session_proposal_time_preferences', 0);
    }
}
