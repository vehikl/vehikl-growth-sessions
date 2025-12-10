<?php

namespace Tests\Feature\GrowthSessionProposals;

use App\Models\GrowthSessionProposal;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Response;
use Tests\TestCase;

class GrowthSessionProposalUpdateTest extends TestCase
{
    public function testCreatorCanUpdateTheirProposal(): void
    {
        $user = User::factory()->create(['is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->create(['creator_id' => $user->id]);

        $response = $this->actingAs($user)->putJson(route('proposals.update', $proposal), [
            'title' => 'Updated Title',
            'topic' => 'Updated Topic',
            'time_preferences' => [
                ['weekday' => 'Friday', 'start_time' => '10:00', 'end_time' => '12:00'],
            ],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('growth_session_proposals', [
            'id' => $proposal->id,
            'title' => 'Updated Title',
            'topic' => 'Updated Topic',
        ]);
    }

    public function testVehiklMembersCanUpdateAnyProposal(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $nonVehiklUser = User::factory()->create(['is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->create(['creator_id' => $nonVehiklUser->id]);

        $response = $this->actingAs($vehiklMember)->putJson(route('proposals.update', $proposal), [
            'title' => 'Updated by Vehikl Member',
            'topic' => $proposal->topic,
            'time_preferences' => [
                ['weekday' => 'Monday', 'start_time' => '14:00', 'end_time' => '17:00'],
            ],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('growth_session_proposals', [
            'id' => $proposal->id,
            'title' => 'Updated by Vehikl Member',
        ]);
    }

    public function testNonCreatorNonVehiklMemberCannotUpdateProposal(): void
    {
        $user1 = User::factory()->create(['is_vehikl_member' => false]);
        $user2 = User::factory()->create(['is_vehikl_member' => false]);
        $proposal = GrowthSessionProposal::factory()->create(['creator_id' => $user1->id]);

        $response = $this->actingAs($user2)->putJson(route('proposals.update', $proposal), [
            'title' => 'Hacked Title',
            'topic' => $proposal->topic,
            'time_preferences' => [
                ['weekday' => 'Monday', 'start_time' => '14:00', 'end_time' => '17:00'],
            ],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('growth_session_proposals', [
            'id' => $proposal->id,
            'title' => 'Hacked Title',
        ]);
    }

    public function testTimePreferencesAreReplacedOnUpdate(): void
    {
        $user = User::factory()->create();
        $proposal = GrowthSessionProposal::factory()->create(['creator_id' => $user->id]);

        \App\Models\GrowthSessionProposalTimePreference::factory()->count(2)->create([
            'growth_session_proposal_id' => $proposal->id,
        ]);

        $this->assertDatabaseCount('growth_session_proposal_time_preferences', 2);

        $response = $this->actingAs($user)->putJson(route('proposals.update', $proposal), [
            'title' => $proposal->title,
            'topic' => $proposal->topic,
            'time_preferences' => [
                ['weekday' => 'Wednesday', 'start_time' => '09:00', 'end_time' => '11:00'],
            ],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseCount('growth_session_proposal_time_preferences', 1);
        $this->assertDatabaseHas('growth_session_proposal_time_preferences', [
            'growth_session_proposal_id' => $proposal->id,
            'weekday' => 'Wednesday',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);
    }

    public function testTagsCanBeUpdated(): void
    {
        $user = User::factory()->create();
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $tag3 = Tag::factory()->create();

        $proposal = GrowthSessionProposal::factory()->create(['creator_id' => $user->id]);
        $proposal->tags()->attach([$tag1->id, $tag2->id]);

        $response = $this->actingAs($user)->putJson(route('proposals.update', $proposal), [
            'title' => $proposal->title,
            'topic' => $proposal->topic,
            'tags' => [$tag2->id, $tag3->id],
            'time_preferences' => [
                ['weekday' => 'Monday', 'start_time' => '14:00', 'end_time' => '17:00'],
            ],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('growth_session_proposal_tag', [
            'growth_session_proposal_id' => $proposal->id,
            'tag_id' => $tag2->id,
        ]);
        $this->assertDatabaseHas('growth_session_proposal_tag', [
            'growth_session_proposal_id' => $proposal->id,
            'tag_id' => $tag3->id,
        ]);
        $this->assertDatabaseMissing('growth_session_proposal_tag', [
            'growth_session_proposal_id' => $proposal->id,
            'tag_id' => $tag1->id,
        ]);
    }
}
