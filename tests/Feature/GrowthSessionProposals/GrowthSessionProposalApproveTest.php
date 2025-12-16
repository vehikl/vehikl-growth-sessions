<?php

namespace Tests\Feature\GrowthSessionProposals;

use App\Models\GrowthSession;
use App\Models\GrowthSessionProposal;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserType;
use Tests\TestCase;

class GrowthSessionProposalApproveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setTestNow('2020-01-15');
    }

    public function testVehiklMemberCanApproveProposal(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $proposalCreator = User::factory()->create(['is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->create([
            'creator_id' => $proposalCreator->id,
            'title' => 'Learn Laravel',
            'topic' => 'Learn advanced Laravel features',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($vehiklMember)->postJson(route('proposals.approve', $proposal), [
            'title' => $proposal->title,
            'topic' => $proposal->topic,
            'location' => 'Discord',
            'date' => '2025-11-03',
            'start_time' => '02:00 pm',
            'end_time' => '05:00 pm',
            'is_public' => true,
            'attendee_limit' => 4,
            'allow_watchers' => true,
        ]);

        $response->assertSuccessful();

        // Proposal should be marked as approved
        $this->assertDatabaseHas('growth_session_proposals', [
            'id' => $proposal->id,
            'status' => 'approved',
        ]);

        // Growth session should be created
        $this->assertDatabaseHas('growth_sessions', [
            'title' => 'Learn Laravel',
            'topic' => 'Learn advanced Laravel features',
            'location' => 'Discord',
            'date' => '2025-11-03',
        ]);
    }

    public function testProposalCreatorBecomesGrowthSessionOwner(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $proposalCreator = User::factory()->create(['is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->create(['creator_id' => $proposalCreator->id]);

        $this->actingAs($vehiklMember)->postJson(route('proposals.approve', $proposal), [
            'title' => $proposal->title,
            'topic' => $proposal->topic,
            'location' => 'Discord',
            'date' => '2025-11-03',
            'start_time' => '02:00 pm',
            'end_time' => '05:00 pm',
        ]);

        $growthSession = GrowthSession::where('title', $proposal->title)->first();

        $this->assertTrue($growthSession->owners->contains($proposalCreator));
        $this->assertDatabaseHas('growth_session_user', [
            'growth_session_id' => $growthSession->id,
            'user_id' => $proposalCreator->id,
            'user_type_id' => UserType::OWNER_ID,
        ]);
    }

    public function testTagsAreTransferredFromProposalToGrowthSession(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $proposal = GrowthSessionProposal::factory()->create();
        $tags = Tag::factory(3)->create();
        $proposal->tags()->attach($tags->pluck('id'));

        $this->actingAs($vehiklMember)->postJson(route('proposals.approve', $proposal), [
            'title' => $proposal->title,
            'topic' => $proposal->topic,
            'location' => 'Discord',
            'date' => '2025-11-03',
            'start_time' => '02:00 pm',
            'end_time' => '05:00 pm',
        ]);

        $growthSession = GrowthSession::where('title', $proposal->title)->firstOrFail();

        $this->assertCount(3, $growthSession->tags);
        $this->assertEqualsCanonicalizing(
            $tags->pluck('id')->toArray(),
            $growthSession->tags->pluck('id')->toArray()
        );
    }

    public function testNonVehiklMemberCannotApproveProposal(): void
    {
        $nonVehiklUser = User::factory()->create(['is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->create();

        $response = $this->actingAs($nonVehiklUser)->postJson(route('proposals.approve', $proposal), [
            'title' => $proposal->title,
            'topic' => $proposal->topic,
            'location' => 'Discord',
            'date' => '2025-11-03',
            'start_time' => '02:00 pm',
            'end_time' => '05:00 pm',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('growth_session_proposals', [
            'id' => $proposal->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('growth_sessions', 0);
    }

    public function testCannotApproveAlreadyApprovedProposal(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $proposal = GrowthSessionProposal::factory()->approved()->create();

        $response = $this->actingAs($vehiklMember)->postJson(route('proposals.approve', $proposal), [
            'title' => $proposal->title,
            'topic' => $proposal->topic,
            'location' => 'Discord',
            'date' => '2025-11-03',
            'start_time' => '02:00 pm',
            'end_time' => '05:00 pm',
        ]);

        $response->assertForbidden();
    }

    public function testApprovalRequiresAllGrowthSessionFields(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $proposal = GrowthSessionProposal::factory()->create();

        $response = $this->actingAs($vehiklMember)->postJson(route('proposals.approve', $proposal), [
            'title' => $proposal->title,
            'topic' => $proposal->topic,
            // Missing location, date, start_time, end_time
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['location', 'date', 'start_time', 'end_time']);
    }
}
