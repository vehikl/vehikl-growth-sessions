<?php

namespace Tests\Feature\GrowthSessionProposals;

use App\Models\GrowthSessionProposal;
use App\Models\User;
use Tests\TestCase;

class GrowthSessionProposalIndexTest extends TestCase
{
    public function testNonVehiklMembersCanOnlySeeTheirOwnProposals(): void
    {
        $this->setTestNow('2025-01-01 10:00:00');

        $user1 = User::factory()->create(['is_vehikl_member' => false]);
        $user2 = User::factory()->create(['is_vehikl_member' => false]);

        $proposal1 = GrowthSessionProposal::factory()->create(['creator_id' => $user1->id]);

        $this->travel(1)->hours();
        $proposal2 = GrowthSessionProposal::factory()->create(['creator_id' => $user2->id]);

        $this->travel(1)->hours();
        $proposal3 = GrowthSessionProposal::factory()->create(['creator_id' => $user1->id]);

        $response = $this->actingAs($user1)->get(route('proposals.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('proposals', 2)
            ->has('proposals.0', fn ($proposal) => $proposal
                ->where('id', $proposal3->id)
                ->etc()
            )
            ->has('proposals.1', fn ($proposal) => $proposal
                ->where('id', $proposal1->id)
                ->etc()
            )
        );
    }

    public function testVehiklMembersCanSeeAllProposals(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $nonVehiklUser = User::factory()->create(['is_vehikl_member' => false]);

        $proposal1 = GrowthSessionProposal::factory()->create(['creator_id' => $vehiklMember->id]);
        $proposal2 = GrowthSessionProposal::factory()->create(['creator_id' => $nonVehiklUser->id]);
        $proposal3 = GrowthSessionProposal::factory()->create(['creator_id' => $vehiklMember->id]);

        $response = $this->actingAs($vehiklMember)->get(route('proposals.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('proposals', 3)
        );
    }

    public function testProposalsAreOrderedByCreatedAtDesc(): void
    {
        $user = User::factory()->vehiklMember()->create();

        $this->setTestNow('2020-01-01');
        $proposal1 = GrowthSessionProposal::factory()->create(['creator_id' => $user->id]);

        $this->setTestNow('2020-01-02');
        $proposal2 = GrowthSessionProposal::factory()->create(['creator_id' => $user->id]);

        $this->setTestNow('2020-01-03');
        $proposal3 = GrowthSessionProposal::factory()->create(['creator_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('proposals.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('proposals.0.id', $proposal3->id)
            ->where('proposals.1.id', $proposal2->id)
            ->where('proposals.2.id', $proposal1->id)
        );
    }

    public function testGuestsCannotViewProposals(): void
    {
        $response = $this->get(route('proposals.index'));

        $response->assertRedirect();
    }
}
