<?php

namespace Tests\Feature\GrowthSessions;

use App\Enums\Role;
use App\Models\AnyDesk;
use App\Models\GrowthSession;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GrowthSessionsStoreTest extends TestCase
{
    public function test_a_growth_session_cannot_be_created_with_an_anydesk_id_that_does_not_exist()
    {
        $growthSessionAttributes = GrowthSession::factory()->raw();
        $user = User::factory()->vehiklMember()->create();

        $growthSessionAttributes['anydesk_id'] = 999999;
        $growthSessionAttributes['start_time'] = '09:00 am';
        $growthSessionAttributes['end_time'] = '10:00 am';

        $this->actingAs($user)->postJson(route('growth_sessions.store'), $growthSessionAttributes)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['anydesk_id' => 'The selected anydesk id is invalid.']);
    }

    public function test_a_user_can_create_a_growth_session_with_an_any_desk()
    {
        $this->setTestNow('2020-01-15');
        $user = User::factory()->vehiklMember()->create();
        $anyDesk = AnyDesk::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('growth_sessions.store'),
                $this->defaultParameters(['anydesk_id' => $anyDesk->id])
            )->assertSuccessful();

        $response->assertSuccessful();
        $this->assertDatabaseHas(GrowthSession::class, ['anydesk_id' => $anyDesk->id]);
    }

    public static function provideWatcherPayload()
    {
        return [
            'allows watchers' => [true],
            'does not allow watchers' => [false],
        ];
    }

    #[DataProvider('provideWatcherPayload')]
    public function test_a_growth_session_can_be_created_with_allow_watchers($watcherFlag)
    {
        $this->setTestNow('2020-01-15');
        $user = User::factory()->vehiklMember()->create();

        $this->actingAs($user)->postJson(
            route('growth_sessions.store'),
            $this->defaultParameters(['allow_watchers' => $watcherFlag])
        )->assertSuccessful();

        $this->assertEquals($watcherFlag, $user->fresh()->growthSessions->first()->allow_watchers);
    }

    public function test_a_growth_session_can_be_created_with_multiple_tags()
    {
        $this->setTestNow('2020-01-15');
        $user = User::factory()->vehiklMember()->create();

        $tags = Tag::factory(3)->create();

        $this->actingAs($user)->postJson(
            route('growth_sessions.store'),
            $this->defaultParameters(['tags' => $tags->pluck('id')])
        )->assertSuccessful();

        $growthSessionTagsNames = $user->fresh()->growthSessions->first()->tags->pluck('name');
        $this->assertEqualsCanonicalizing($tags->pluck('name'), $growthSessionTagsNames);
    }

    public function test_a_growth_session_cannot_be_created_during_the_weekend()
    {
        $this->setTestNow('2020-01-15');
        $growthSessionAttributes = GrowthSession::factory()->raw();
        $user = User::factory()->vehiklMember()->create();

        $growthSessionAttributes['date'] = '2020-01-18';
        $growthSessionAttributes['start_time'] = '09:00 am';
        $growthSessionAttributes['end_time'] = '10:00 am';

        $this->actingAs($user)->postJson(route('growth_sessions.store'), $growthSessionAttributes)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['date' => 'A growth session can not be hosted on weekends.']);
    }

    public function test_a_user_can_create_a_publicly_available_growth_session()
    {
        $this->setTestNow('2020-01-15');

        /** @var User $user */
        $user = User::factory()->create(['is_vehikl_member' => true]);

        $this->actingAs($user)->postJson(
            route('growth_sessions.store'),
            $this->defaultParameters(['is_public' => true])
        );

        $this->assertDatabaseHas(GrowthSession::class, ['is_public' => true]);
    }

    public function test_vehikl_members_can_create_a_growth_session(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSessionsAttributes = GrowthSession::factory()->make()->toArray();

        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.store'), $growthSessionsAttributes)
            ->assertSuccessful();

        $this->assertNotEmpty(GrowthSession::query()->where('title', $growthSessionsAttributes['title'])->first());
    }

    public function test_non_vehikl_members_cannot_create_a_growth_session(): void
    {
        /** @var User $nonVehiklMember */
        $nonVehiklMember = User::factory()->create();
        $growthSessionsAttributes = GrowthSession::factory()->make()->toArray();

        $this->actingAs($nonVehiklMember)
            ->post(route('growth_sessions.store'), $growthSessionsAttributes)
            ->assertForbidden();

        $this->assertEmpty(GrowthSession::query()->where('title', $growthSessionsAttributes['title'])->first());
    }

    public function test_an_attendee_limit_can_be_set_when_creating_at_growth_session()
    {
        $this->setTestNow('2020-01-15');

        $user = User::factory()->vehiklMember()->create();

        $expectedAttendeeLimit = 420;
        $this->actingAs($user)->postJson(
            route('growth_sessions.store'),
            $this->defaultParameters(['attendee_limit' => $expectedAttendeeLimit])
        )->assertSuccessful();

        $this->assertEquals($expectedAttendeeLimit, $user->growthSessions->first()->attendee_limit);
    }

    public function test_an_attendee_limit_cannot_be_less_than_two()
    {
        $vehiklMember = User::factory()->vehiklMember()->create();

        $expectedAttendeeLimit = 1;
        $this->actingAs($vehiklMember)->postJson(
            route('growth_sessions.store'),
            $this->defaultParameters(['attendee_limit' => $expectedAttendeeLimit])
        )->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('attendee_limit');
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
            'discord_channel' => null,
        ], $params);
    }

    public function test_response_reflects_database_defaults_for_fields_omitted_from_the_request(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();

        $this->actingAs($vehiklMember)
            ->postJson(route('growth_sessions.store'), $this->defaultParameters())
            ->assertSuccessful()
            ->assertJsonPath('is_public', false)
            ->assertJsonPath('allow_watchers', true);
    }

    public function test_includes_attendees_information_even_for_a_newly_created_growth_session(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSessionsAttributes = GrowthSession::factory()->make()->toArray();

        // The owner counts as an attendee for capacity purposes (see GrowthSession::attendees()), so
        // they're expected here too - matching what a subsequent GET of the same session would show.
        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.store'), $growthSessionsAttributes)
            ->assertJsonCount(1, 'attendees')
            ->assertJsonPath('attendees.0.id', $vehiklMember->id);
    }

    public function test_a_user_cannot_create_two_growth_sessions_in_the_same_time_slot()
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSessionsAttributes = GrowthSession::factory()->make()->toArray();

        $this->actingAs($vehiklMember)
            ->postJson(route('growth_sessions.store'), $growthSessionsAttributes)
            ->assertSuccessful();

        $this->actingAs($vehiklMember)
            ->postJson(route('growth_sessions.store'), $growthSessionsAttributes)
            ->assertUnprocessable();
    }

    public function test_the_owner_is_only_stored_once_in_the_database()
    {
        $newGrowthSession = GrowthSession::factory()->make()->toArray();
        $host = User::factory()->vehiklMember()->create();

        $this->actingAs($host)
            ->post(route('growth_sessions.store', $newGrowthSession))
            ->assertSuccessful();

        $growthSession = GrowthSession::first();
        $ownerEntries = $growthSession->members()
            ->withPivot('user_type_id')
            ->wherePivot('user_id', $host->id)
            ->get();

        $this->assertCount(1, $ownerEntries);
        $this->assertEquals(Role::Owner->value, $ownerEntries->first()->pivot->user_type_id);
    }
}
