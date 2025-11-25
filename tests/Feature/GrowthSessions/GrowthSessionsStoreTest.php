<?php

namespace Tests\Feature\GrowthSessions;

use App\AnyDesk;
use App\GrowthSession;
use App\Tag;
use App\User;
use App\UserType;
use Illuminate\Http\Response;
use Tests\TestCase;

class GrowthSessionsStoreTest extends TestCase
{
    public function testAGrowthSessionCannotBeCreatedWithAnAnydeskIdThatDoesNotExist()
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

    public function testAUserCanCreateAGrowthSessionWithAnAnyDesk()
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

    public function provideWatcherPayload()
    {
        return [
            'allows watchers' => [true],
            'does not allow watchers' => [false],
        ];
    }

    /** @dataProvider provideWatcherPayload */
    public function testAGrowthSessionCanBeCreatedWithAllowWatchers($watcherFlag)
    {
        $this->setTestNow('2020-01-15');
        $user = User::factory()->vehiklMember()->create();

        $this->actingAs($user)->postJson(
            route('growth_sessions.store'),
            $this->defaultParameters(['allow_watchers' => $watcherFlag])
        )->assertSuccessful();

        $this->assertEquals($watcherFlag, $user->fresh()->growthSessions->first()->allow_watchers);
    }

    public function testAGrowthSessionCanBeCreatedWithMultipleTags()
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

    public function testAGrowthSessionCannotBeCreatedDuringTheWeekend()
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

    public function testAUserCanCreateAPubliclyAvailableGrowthSession()
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

    public function testVehiklMembersCanCreateAGrowthSession(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSessionsAttributes = GrowthSession::factory()->make()->toArray();

        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.store'), $growthSessionsAttributes)
            ->assertSuccessful();

        $this->assertNotEmpty(GrowthSession::query()->where('title', $growthSessionsAttributes['title'])->first());
    }

    public function testNonVehiklMembersCannotCreateAGrowthSession(): void
    {
        /** @var User $nonVehiklMember */
        $nonVehiklMember = User::factory()->create();
        $growthSessionsAttributes = GrowthSession::factory()->make()->toArray();

        $this->actingAs($nonVehiklMember)
            ->post(route('growth_sessions.store'), $growthSessionsAttributes)
            ->assertForbidden();

        $this->assertEmpty(GrowthSession::query()->where('title', $growthSessionsAttributes['title'])->first());
    }

    public function testAnAttendeeLimitCanBeSetWhenCreatingAtGrowthSession()
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

    public function testAnAttendeeLimitCannotBeLessThanTwo()
    {
        $vehiklMember = User::factory()->vehiklMember()->create();

        $expectedAttendeeLimit = 1;
        $this->actingAs($vehiklMember)->postJson(
            route('growth_sessions.store'),
            $this->defaultParameters(['attendee_limit' => $expectedAttendeeLimit])
        )->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['attendee_limit' => 'The attendee limit must be at least 2']);
    }

    private function defaultParameters(array $params = []): array
    {
        return array_merge([
            'topic' => 'The fundamentals of foo',
            'title' => 'Foo',
            'location' => 'At the central mobbing area',
            'start_time' => now()->format('h:i a'),
            'date' => today(),
            'discord_channel' => null,
        ], $params);
    }

    public function testIncludesAttendeesInformationEvenForANewlyCreatedGrowthSession(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSessionsAttributes = GrowthSession::factory()->make()->toArray();

        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.store'), $growthSessionsAttributes)
            ->assertJsonFragment([
                'attendees' => []
            ]);
    }

    public function testAUserCannotCreateTwoGrowthSessionsInTheSameTimeSlot()
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

    public function testTheOwnerIsOnlyStoredOnceInTheDatabase()
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
        $this->assertEquals(UserType::OWNER_ID, $ownerEntries->first()->pivot->user_type_id);
    }
}
