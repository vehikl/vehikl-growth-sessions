<?php

namespace Tests\Unit\Policies;

use App\Models\GrowthSessionProposal;
use App\Models\User;
use App\Policies\GrowthSessionProposalPolicy;
use Tests\TestCase;

class GrowthSessionProposalPolicyTest extends TestCase
{
    private GrowthSessionProposalPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new GrowthSessionProposalPolicy();
    }

    public function testAnyAuthenticatedUserCanCreateProposals(): void
    {
        $nonVehiklUser = User::factory()->make(['is_vehikl_member' => false]);
        $vehiklUser = User::factory()->make(['is_vehikl_member' => true]);

        $this->assertTrue($this->policy->create($nonVehiklUser));
        $this->assertTrue($this->policy->create($vehiklUser));
    }

    public function testCreatorCanViewTheirProposal(): void
    {
        $user = User::factory()->make(['id' => 1, 'is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->make(['creator_id' => 1]);

        $this->assertTrue($this->policy->view($user, $proposal));
    }

    public function testVehiklMemberCanViewAnyProposal(): void
    {
        $vehiklMember = User::factory()->make(['id' => 1, 'is_vehikl_member' => true]);
        $proposal = GrowthSessionProposal::factory()->make(['creator_id' => 999]);

        $this->assertTrue($this->policy->view($vehiklMember, $proposal));
    }

    public function testNonCreatorNonVehiklMemberCannotViewProposal(): void
    {
        $user = User::factory()->make(['id' => 1, 'is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->make(['creator_id' => 2]);

        $this->assertFalse($this->policy->view($user, $proposal));
    }

    public function testCreatorCanUpdateTheirProposal(): void
    {
        $user = User::factory()->make(['id' => 1, 'is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->make(['creator_id' => 1]);

        $this->assertTrue($this->policy->update($user, $proposal));
    }

    public function testVehiklMemberCanUpdateAnyProposal(): void
    {
        $vehiklMember = User::factory()->make(['id' => 1, 'is_vehikl_member' => true]);
        $proposal = GrowthSessionProposal::factory()->make(['creator_id' => 999]);

        $this->assertTrue($this->policy->update($vehiklMember, $proposal));
    }

    public function testNonCreatorNonVehiklMemberCannotUpdateProposal(): void
    {
        $user = User::factory()->make(['id' => 1, 'is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->make(['creator_id' => 2]);

        $this->assertFalse($this->policy->update($user, $proposal));
    }

    public function testCreatorCanDeleteTheirProposal(): void
    {
        $user = User::factory()->make(['id' => 1, 'is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->make(['creator_id' => 1]);

        $this->assertTrue($this->policy->delete($user, $proposal));
    }

    public function testVehiklMemberCanDeleteAnyProposal(): void
    {
        $vehiklMember = User::factory()->make(['id' => 1, 'is_vehikl_member' => true]);
        $proposal = GrowthSessionProposal::factory()->make(['creator_id' => 999]);

        $this->assertTrue($this->policy->delete($vehiklMember, $proposal));
    }

    public function testNonCreatorNonVehiklMemberCannotDeleteProposal(): void
    {
        $user = User::factory()->make(['id' => 1, 'is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->make(['creator_id' => 2]);

        $this->assertFalse($this->policy->delete($user, $proposal));
    }

    public function testOnlyVehiklMembersCanApproveProposals(): void
    {
        $vehiklMember = User::factory()->make(['is_vehikl_member' => true]);
        $nonVehiklMember = User::factory()->make(['is_vehikl_member' => false]);
        $pendingProposal = GrowthSessionProposal::factory()->make(['status' => 'pending']);

        $this->assertTrue($this->policy->approve($vehiklMember, $pendingProposal));
        $this->assertFalse($this->policy->approve($nonVehiklMember, $pendingProposal));
    }

    public function testCannotApproveAlreadyApprovedProposal(): void
    {
        $vehiklMember = User::factory()->make(['is_vehikl_member' => true]);
        $approvedProposal = GrowthSessionProposal::factory()->make(['status' => 'approved']);

        $this->assertFalse($this->policy->approve($vehiklMember, $approvedProposal));
    }

    public function testCannotApproveRejectedProposal(): void
    {
        $vehiklMember = User::factory()->make(['is_vehikl_member' => true]);
        $rejectedProposal = GrowthSessionProposal::factory()->make(['status' => 'rejected']);

        $this->assertFalse($this->policy->approve($vehiklMember, $rejectedProposal));
    }
}
