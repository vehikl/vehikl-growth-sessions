<?php

namespace Tests\Feature\GrowthSessionProposals;

use App\Models\GrowthSessionProposal;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Response;
use Tests\TestCase;

class GrowthSessionProposalStoreTest extends TestCase
{
    public function testAnyAuthenticatedUserCanCreateAProposal(): void
    {
        $user = User::factory()->create(['is_vehikl_member' => false]);
        $tags = Tag::factory(2)->create();

        $response = $this->actingAs($user)->postJson(route('proposals.store'), [
            'title' => 'Learn Laravel 12',
            'topic' => 'I would like to learn Laravel 12 features',
            'tags' => $tags->pluck('id')->toArray(),
            'time_preferences' => [
                ['weekday' => 'Monday', 'start_time' => '14:00', 'end_time' => '17:00'],
                ['weekday' => 'Wednesday', 'start_time' => '10:00', 'end_time' => '12:00'],
            ],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('growth_session_proposals', [
            'title' => 'Learn Laravel 12',
            'topic' => 'I would like to learn Laravel 12 features',
            'creator_id' => $user->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('growth_session_proposal_time_preferences', 2);
    }

    public function testVehiklMembersCanAlsoCreateProposals(): void
    {
        $user = User::factory()->vehiklMember()->create();

        $response = $this->actingAs($user)->postJson(route('proposals.store'), [
            'title' => 'Vue 3 Composition API',
            'topic' => 'Deep dive into Composition API',
            'time_preferences' => [
                ['weekday' => 'Tuesday', 'start_time' => '09:00', 'end_time' => '11:00'],
            ],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('growth_session_proposals', [
            'creator_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function testGuestsCannotCreateProposals(): void
    {
        $response = $this->postJson(route('proposals.store'), [
            'title' => 'Test Proposal',
            'topic' => 'Test Topic',
            'time_preferences' => [
                ['weekday' => 'Monday', 'start_time' => '14:00', 'end_time' => '17:00'],
            ],
        ]);

        $response->assertUnauthorized();
    }

    public function testTitleIsRequired(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('proposals.store'), [
            'topic' => 'Test Topic',
            'time_preferences' => [
                ['weekday' => 'Monday', 'start_time' => '14:00', 'end_time' => '17:00'],
            ],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['title']);
    }

    public function testTopicIsRequired(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('proposals.store'), [
            'title' => 'Test Title',
            'time_preferences' => [
                ['weekday' => 'Monday', 'start_time' => '14:00', 'end_time' => '17:00'],
            ],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['topic']);
    }

    public function testTimePreferencesAreRequired(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('proposals.store'), [
            'title' => 'Test Title',
            'topic' => 'Test Topic',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['time_preferences']);
    }

    public function testAtLeastOneTimePreferenceIsRequired(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('proposals.store'), [
            'title' => 'Test Title',
            'topic' => 'Test Topic',
            'time_preferences' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['time_preferences']);
    }

    public function testWeekdayMustBeValid(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('proposals.store'), [
            'title' => 'Test Title',
            'topic' => 'Test Topic',
            'time_preferences' => [
                ['weekday' => 'Saturday', 'start_time' => '14:00', 'end_time' => '17:00'],
            ],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['time_preferences.0.weekday']);
    }

    public function testTagsMustExist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('proposals.store'), [
            'title' => 'Test Title',
            'topic' => 'Test Topic',
            'tags' => [99999],
            'time_preferences' => [
                ['weekday' => 'Monday', 'start_time' => '14:00', 'end_time' => '17:00'],
            ],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['tags.0']);
    }

    public function testProposalCanBeCreatedWithTags(): void
    {
        $user = User::factory()->create();
        $tags = Tag::factory(3)->create();

        $response = $this->actingAs($user)->postJson(route('proposals.store'), [
            'title' => 'Test Title',
            'topic' => 'Test Topic',
            'tags' => $tags->pluck('id')->toArray(),
            'time_preferences' => [
                ['weekday' => 'Monday', 'start_time' => '14:00', 'end_time' => '17:00'],
            ],
        ]);

        $response->assertSuccessful();

        $proposal = GrowthSessionProposal::first();
        $this->assertCount(3, $proposal->tags);
        $this->assertEqualsCanonicalizing($tags->pluck('id')->toArray(), $proposal->tags->pluck('id')->toArray());
    }
}
