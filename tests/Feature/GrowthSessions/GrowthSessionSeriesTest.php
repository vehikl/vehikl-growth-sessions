<?php

namespace Tests\Feature\GrowthSessions;

use App\Enums\Role;
use App\Models\GrowthSession;
use App\Models\Series;
use App\Models\User;
use Illuminate\Http\Response;
use Tests\TestCase;

class GrowthSessionSeriesTest extends TestCase
{
    public function test_naming_a_series_when_creating_a_growth_session_files_it_under_that_series()
    {
        $this->setTestNowToASafeWednesday();
        $user = User::factory()->vehiklMember()->create();

        $this->actingAs($user)
            ->postJson(route('growth_sessions.store'), $this->defaultParameters(['series_name' => 'Vue Deep Dive']))
            ->assertSuccessful()
            ->assertJsonPath('series_name', 'Vue Deep Dive');

        $this->assertDatabaseHas(Series::class, ['name' => 'Vue Deep Dive', 'owner_id' => $user->id]);
    }

    public function test_starting_a_series_makes_the_member_who_started_it_its_owner()
    {
        $this->setTestNowToASafeWednesday();
        $user = User::factory()->vehiklMember()->create();

        $this->actingAs($user)
            ->postJson(route('growth_sessions.store'), $this->defaultParameters(['series_name' => 'Vue Deep Dive']))
            ->assertSuccessful();

        $this->assertTrue(Series::query()->firstWhere('name', 'Vue Deep Dive')->owner->is($user));
    }

    public function test_a_series_name_is_stored_as_the_members_own_thread_already_spells_it()
    {
        $this->setTestNowToASafeWednesday();
        $user = User::factory()->vehiklMember()->create();
        GrowthSession::factory()->inSeries('Vue Deep Dive', $user)->create();

        $this->actingAs($user)
            ->postJson(route('growth_sessions.store'), $this->defaultParameters(['series_name' => 'vue deep dive']))
            ->assertSuccessful()
            ->assertJsonPath('series_name', 'Vue Deep Dive');

        $this->assertEquals(['Vue Deep Dive'], Series::namesOwnedBy($user)->all());
    }

    public function test_a_series_name_is_trimmed_before_it_is_stored()
    {
        $this->setTestNowToASafeWednesday();
        $user = User::factory()->vehiklMember()->create();

        $this->actingAs($user)
            ->postJson(route('growth_sessions.store'), $this->defaultParameters(['series_name' => '  Vue Deep Dive  ']))
            ->assertSuccessful()
            ->assertJsonPath('series_name', 'Vue Deep Dive');
    }

    // A thread is somebody's to run: the name of one is not a claim on the words.

    public function test_a_name_somebody_elses_series_goes_by_starts_a_series_of_the_members_own()
    {
        $this->setTestNowToASafeWednesday();
        $someoneElse = User::factory()->vehiklMember()->create();
        GrowthSession::factory()->inSeries('Vue Deep Dive', $someoneElse)->create();
        $user = User::factory()->vehiklMember()->create();

        $this->actingAs($user)
            ->postJson(route('growth_sessions.store'), $this->defaultParameters(['series_name' => 'Vue Deep Dive']))
            ->assertSuccessful()
            ->assertJsonPath('series_name', 'Vue Deep Dive');

        $this->assertDatabaseCount(Series::class, 2);
        $this->assertEquals(['Vue Deep Dive'], Series::namesOwnedBy($user)->all());
        $this->assertEquals(['Vue Deep Dive'], Series::namesOwnedBy($someoneElse)->all());
    }

    public function test_a_growth_session_created_without_a_series_belongs_to_none()
    {
        $this->setTestNowToASafeWednesday();
        $user = User::factory()->vehiklMember()->create();

        $this->actingAs($user)
            ->postJson(route('growth_sessions.store'), $this->defaultParameters())
            ->assertSuccessful()
            ->assertJsonPath('series_name', null);
    }

    public function test_a_blank_series_name_files_a_growth_session_under_none()
    {
        $this->setTestNowToASafeWednesday();
        $user = User::factory()->vehiklMember()->create();

        $this->actingAs($user)
            ->postJson(route('growth_sessions.store'), $this->defaultParameters(['series_name' => '   ']))
            ->assertSuccessful()
            ->assertJsonPath('series_name', null);

        $this->assertDatabaseCount(Series::class, 0);
    }

    public function test_a_series_name_cannot_be_longer_than_the_column_holds()
    {
        $this->setTestNowToASafeWednesday();
        $user = User::factory()->vehiklMember()->create();

        $this->actingAs($user)
            ->postJson(route('growth_sessions.store'), $this->defaultParameters(['series_name' => str_repeat('a', 46)]))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('series_name');
    }

    public function test_an_owner_can_move_an_upcoming_growth_session_into_another_series()
    {
        [$owner, $growthSession] = $this->ownedGrowthSessionInASeries();

        $this->actingAs($owner)
            ->putJson(route('growth_sessions.update', ['growth_session' => $growthSession->id]), ['series_name' => 'Friday Pairing'])
            ->assertSuccessful()
            ->assertJsonPath('series_name', 'Friday Pairing');

        $this->assertDatabaseHas(Series::class, ['name' => 'Friday Pairing', 'owner_id' => $owner->id]);
    }

    public function test_an_owner_can_take_an_upcoming_growth_session_out_of_its_series()
    {
        [$owner, $growthSession] = $this->ownedGrowthSessionInASeries();

        $this->actingAs($owner)
            ->putJson(route('growth_sessions.update', ['growth_session' => $growthSession->id]), ['series_name' => null])
            ->assertSuccessful()
            ->assertJsonPath('series_name', null);
    }

    public function test_an_update_that_says_nothing_about_the_series_leaves_the_growth_session_in_it()
    {
        [$owner, $growthSession] = $this->ownedGrowthSessionInASeries();

        $this->actingAs($owner)
            ->putJson(route('growth_sessions.update', ['growth_session' => $growthSession->id]), ['title' => 'A new title'])
            ->assertSuccessful()
            ->assertJsonPath('series_name', 'Vue Deep Dive');
    }

    // A series lives for exactly as long as it holds a session.

    public function test_a_series_stops_existing_once_the_last_growth_session_leaves_it()
    {
        [$owner, $growthSession] = $this->ownedGrowthSessionInASeries();

        $this->actingAs($owner)
            ->putJson(route('growth_sessions.update', ['growth_session' => $growthSession->id]), ['series_name' => null])
            ->assertSuccessful();

        $this->assertDatabaseCount(Series::class, 0);
    }

    public function test_a_series_stops_existing_once_its_last_growth_session_is_deleted()
    {
        [$owner, $growthSession] = $this->ownedGrowthSessionInASeries();

        $this->actingAs($owner)
            ->deleteJson(route('growth_sessions.destroy', ['growth_session' => $growthSession->id]))
            ->assertSuccessful();

        $this->assertDatabaseCount(Series::class, 0);
    }

    public function test_a_series_survives_a_growth_session_leaving_it_while_others_remain()
    {
        [$owner, $growthSession] = $this->ownedGrowthSessionInASeries();
        GrowthSession::factory()->inSeries('Vue Deep Dive', $owner)->create();

        $this->actingAs($owner)
            ->putJson(route('growth_sessions.update', ['growth_session' => $growthSession->id]), ['series_name' => null])
            ->assertSuccessful();

        $this->assertEquals(['Vue Deep Dive'], Series::namesOwnedBy($owner)->all());
    }

    public function test_the_series_index_lists_the_members_own_series_in_alphabetical_order()
    {
        $user = User::factory()->vehiklMember()->create();
        GrowthSession::factory()->inSeries('Zebra Sessions', $user)->create();
        GrowthSession::factory()->inSeries('Apple Sessions', $user)->create();
        GrowthSession::factory()->inSeries('Apple Sessions', $user)->create();
        GrowthSession::factory()->create();

        $this->actingAs($user)
            ->getJson(route('series.index'))
            ->assertSuccessful()
            ->assertExactJson(['Apple Sessions', 'Zebra Sessions']);
    }

    public function test_the_series_index_leaves_out_series_somebody_else_runs()
    {
        $user = User::factory()->vehiklMember()->create();
        $someoneElse = User::factory()->vehiklMember()->create();
        GrowthSession::factory()->inSeries('Mine', $user)->create();
        GrowthSession::factory()->inSeries('Theirs', $someoneElse)->create();

        $this->actingAs($user)
            ->getJson(route('series.index'))
            ->assertSuccessful()
            ->assertExactJson(['Mine']);
    }

    public function test_the_series_index_is_closed_to_a_guest()
    {
        $this->getJson(route('series.index'))->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    // The point of the filing endpoint: `update` is closed on a session that has already happened.

    public function test_an_owner_can_file_a_growth_session_that_has_already_happened()
    {
        [$owner, $growthSession] = $this->ownedGrowthSessionInASeries(['date' => today()->subMonth()]);

        $this->actingAs($owner)
            ->putJson(route('growth_sessions.series.file', ['growth_session' => $growthSession->id]), ['series_name' => 'Friday Pairing'])
            ->assertSuccessful()
            ->assertExactJson(['series_name' => 'Friday Pairing']);

        $this->assertEquals('Friday Pairing', $growthSession->fresh()->series_name);
    }

    public function test_filing_a_past_growth_session_is_refused_through_the_update_endpoint()
    {
        [$owner, $growthSession] = $this->ownedGrowthSessionInASeries(['date' => today()->subMonth()]);

        $this->actingAs($owner)
            ->putJson(route('growth_sessions.update', ['growth_session' => $growthSession->id]), ['series_name' => 'Friday Pairing'])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_an_owner_can_take_a_growth_session_that_has_already_happened_out_of_its_series()
    {
        [$owner, $growthSession] = $this->ownedGrowthSessionInASeries(['date' => today()->subMonth()]);

        $this->actingAs($owner)
            ->putJson(route('growth_sessions.series.file', ['growth_session' => $growthSession->id]), ['series_name' => null])
            ->assertSuccessful()
            ->assertExactJson(['series_name' => null]);

        $this->assertNull($growthSession->fresh()->series_name);
    }

    public function test_filing_adopts_the_spelling_the_members_own_thread_goes_by()
    {
        [$owner, $growthSession] = $this->ownedGrowthSessionInASeries(['date' => today()->subMonth()]);
        GrowthSession::factory()->inSeries('Friday Pairing', $owner)->create();

        $this->actingAs($owner)
            ->putJson(route('growth_sessions.series.file', ['growth_session' => $growthSession->id]), ['series_name' => 'FRIDAY PAIRING'])
            ->assertSuccessful()
            ->assertExactJson(['series_name' => 'Friday Pairing']);
    }

    public function test_filing_under_a_name_somebody_else_uses_starts_a_series_of_the_owners_own()
    {
        [$owner, $growthSession] = $this->ownedGrowthSessionInASeries(['date' => today()->subMonth()]);
        $someoneElse = User::factory()->vehiklMember()->create();
        GrowthSession::factory()->inSeries('Friday Pairing', $someoneElse)->create();

        $this->actingAs($owner)
            ->putJson(route('growth_sessions.series.file', ['growth_session' => $growthSession->id]), ['series_name' => 'Friday Pairing'])
            ->assertSuccessful()
            ->assertExactJson(['series_name' => 'Friday Pairing']);

        $this->assertTrue($growthSession->fresh()->series->owner->is($owner));
    }

    public function test_only_the_owner_can_file_a_growth_session_under_a_series()
    {
        [, $growthSession] = $this->ownedGrowthSessionInASeries();
        $someoneElse = User::factory()->vehiklMember()->create();

        $this->actingAs($someoneElse)
            ->putJson(route('growth_sessions.series.file', ['growth_session' => $growthSession->id]), ['series_name' => 'Friday Pairing'])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_a_guest_cannot_file_a_growth_session_under_a_series()
    {
        [, $growthSession] = $this->ownedGrowthSessionInASeries();

        $this->putJson(route('growth_sessions.series.file', ['growth_session' => $growthSession->id]), ['series_name' => 'Friday Pairing'])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_filing_requires_the_payload_to_say_what_the_series_is()
    {
        [$owner, $growthSession] = $this->ownedGrowthSessionInASeries();

        $this->actingAs($owner)
            ->putJson(route('growth_sessions.series.file', ['growth_session' => $growthSession->id]), [])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('series_name');
    }

    /** @return array{0: User, 1: GrowthSession} */
    private function ownedGrowthSessionInASeries(array $attributes = []): array
    {
        $owner = User::factory()->vehiklMember()->create();
        $growthSession = GrowthSession::factory()->inSeries('Vue Deep Dive', $owner)->create($attributes);
        $growthSession->owners()->attach($owner, ['user_type_id' => Role::Owner->value]);

        return [$owner, $growthSession];
    }

    private function defaultParameters(array $params = []): array
    {
        return array_merge([
            'topic' => 'The fundamentals of foo',
            'title' => 'Foo',
            'location' => 'At the central mobbing area',
            'start_time' => now()->format('h:i a'),
            'end_time' => now()->addHour()->format('h:i a'),
            'date' => today(),
        ], $params);
    }
}
