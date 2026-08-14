<?php

namespace Tests\Feature\GrowthSessions;

use App\Http\Resources\GrowthSession as GrowthSessionResource;
use App\Models\GrowthSession;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GrowthSessionsIndexTest extends TestCase
{
    /** Builds the exact payload the API would produce for $growthSession, as seen by the current `actingAs` user (or a guest, if none). */
    private function resourceArray(GrowthSession $growthSession): array
    {
        $resource = new GrowthSessionResource(
            $growthSession->fresh(GrowthSession::RESOURCE_RELATIONS)
        );

        // actingAs() doesn't wire up request()->user() until an actual HTTP dispatch, so bind it to
        // the Auth guard's user directly.
        $request = request();
        $request->setUserResolver(fn () => auth()->user());

        // resolve() (not toArray()) so conditional fields like share_url are filtered the same way a
        // real response is; the json_encode/decode round trip matches what assertJson() compares against.
        return json_decode(json_encode($resource->resolve($request)), true);
    }

    public function test_it_can_provide_all_growth_sessions_of_the_current_week_for_authenticated_user()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');

        $mondayGrowthSession = GrowthSession::factory()
            ->create(['date' => $monday, 'start_time' => '03:30 pm', 'attendee_limit' => 4]);
        $lateWednesdayGrowthSession = GrowthSession::factory()
            ->create(['date' => $monday->addDays(2), 'start_time' => '04:30 pm', 'attendee_limit' => 4]);
        $earlyWednesdayGrowthSession = GrowthSession::factory()
            ->create(['date' => $monday->addDays(2), 'start_time' => '03:30 pm', 'attendee_limit' => 4]);
        $fridayGrowthSession = GrowthSession::factory()
            ->create(['date' => $monday->addDays(4), 'start_time' => '03:30 pm', 'attendee_limit' => 4]);
        GrowthSession::factory()
            ->create([
                'date' => $monday->addDays(8),
                'start_time' => '03:30 pm',
                'attendee_limit' => 4,
            ]); // GrowthSessions on another week

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $expectedResponse = [
            $monday->toDateString() => [$this->resourceArray($mondayGrowthSession)],
            $monday->addDays(1)->toDateString() => [],
            $monday->addDays(2)->toDateString() => [
                $this->resourceArray($earlyWednesdayGrowthSession),
                $this->resourceArray($lateWednesdayGrowthSession),
            ],
            $monday->addDays(3)->toDateString() => [],
            $monday->addDays(4)->toDateString() => [
                $this->resourceArray($fridayGrowthSession),
            ],
        ];

        $this->getJson(route('growth_sessions.week'))
            ->assertSuccessful()
            ->assertJson($expectedResponse);
    }

    /**
     * The `owner` accessor and the visibility filter both used to run their own query per session
     * (an N+1 on owners, plus fully hydrating relations on sessions the viewer can't even see). This
     * asserts the query count stays flat as the number of visible sessions grows, rather than
     * asserting a specific number that would just be an arbitrary magic number to update later.
     */
    public function test_the_week_endpoint_query_count_does_not_grow_with_the_number_of_visible_sessions()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        $user = User::factory()->create();

        $queryCountFor = function (int $visibleSessionCount) use ($monday, $user) {
            GrowthSession::factory()->count($visibleSessionCount)->create(['date' => $monday, 'is_public' => true]);
            // Owned by someone else and not public, so the viewer's visibility filter drops these -
            // present to prove their relations are never fully hydrated either.
            GrowthSession::factory()->count($visibleSessionCount)->create(['date' => $monday, 'is_public' => false]);

            $this->actingAs($user);

            DB::enableQueryLog();
            $this->getJson(route('growth_sessions.week'))->assertSuccessful();
            $queryCount = count(DB::getQueryLog());
            DB::flushQueryLog();
            DB::disableQueryLog();

            return $queryCount;
        };

        $this->assertSame($queryCountFor(2), $queryCountFor(6));
    }

    public function test_it_can_provide_all_growth_sessions_of_the_current_week_for_authenticated_user_even_on_fridays()
    {
        $this->setTestNow('Next Friday');

        $mondayGrowthSession = GrowthSession::factory()
            ->create(['date' => Carbon::parse('Last Monday'), 'attendee_limit' => 4]);
        $fridayGrowthSession = GrowthSession::factory()
            ->create(['date' => today(), 'attendee_limit' => 4]);

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $expectedResponse = [
            Carbon::parse('Last Monday')->toDateString() => [$this->resourceArray($mondayGrowthSession)],
            Carbon::parse('Last Tuesday')->toDateString() => [],
            Carbon::parse('Last Wednesday')->toDateString() => [],
            Carbon::parse('Last Thursday')->toDateString() => [],
            today()->toDateString() => [$this->resourceArray($fridayGrowthSession)],
        ];

        $response = $this->getJson(route('growth_sessions.week'));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function test_it_can_provide_all_growth_sessions_of_a_specified_week_for_authenticated_user_if_a_date_is_given()
    {
        $weekThatHasNoGrowthSessions = '2020-05-25';
        $this->setTestNow($weekThatHasNoGrowthSessions);
        $weekThatHasTheGrowthSessions = '2020-05-04';
        $mondayOfWeekWithGrowthSessions = CarbonImmutable::parse($weekThatHasTheGrowthSessions);

        $mondayGrowthSession = GrowthSession::factory()
            ->create(['date' => $mondayOfWeekWithGrowthSessions, 'start_time' => '03:30 pm', 'attendee_limit' => 4]);
        $lateWednesdayGrowthSession = GrowthSession::factory()
            ->create([
                'date' => $mondayOfWeekWithGrowthSessions->addDays(2),
                'start_time' => '04:30 pm',
                'attendee_limit' => 4,
            ]);
        $earlyWednesdayGrowthSession = GrowthSession::factory()
            ->create([
                'date' => $mondayOfWeekWithGrowthSessions->addDays(2),
                'start_time' => '03:30 pm',
                'attendee_limit' => 4,
            ]);
        $fridayGrowthSession = GrowthSession::factory()
            ->create([
                'date' => $mondayOfWeekWithGrowthSessions->addDays(4),
                'start_time' => '03:30 pm',
                'attendee_limit' => 4,
            ]);

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $expectedResponse = [
            $mondayOfWeekWithGrowthSessions->toDateString() => [$this->resourceArray($mondayGrowthSession)],
            $mondayOfWeekWithGrowthSessions->addDays(1)->toDateString() => [],
            $mondayOfWeekWithGrowthSessions->addDays(2)->toDateString() => [
                $this->resourceArray($earlyWednesdayGrowthSession),
                $this->resourceArray($lateWednesdayGrowthSession),
            ],
            $mondayOfWeekWithGrowthSessions->addDays(3)->toDateString() => [],
            $mondayOfWeekWithGrowthSessions->addDays(4)->toDateString() => [
                $this->resourceArray($fridayGrowthSession),
            ],
        ];

        $response = $this->getJson(route(
            'growth_sessions.week',
            ['date' => $weekThatHasTheGrowthSessions]
        ));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function test_it_shows_next_weeks_growth_sessions_if_specified_date_falls_on_weekend()
    {
        CarbonImmutable::setTestNow('2020-01-08');
        $saturdayOfThisWeek = CarbonImmutable::parse('next Saturday');
        $mondayOfThisWeek = $saturdayOfThisWeek->modify('last Monday');
        $mondayOfNextWeek = $mondayOfThisWeek->addWeek();

        $thisWeeksGrowthSession = GrowthSession::factory()
            ->create(['date' => $mondayOfThisWeek, 'start_time' => '03:30 pm', 'attendee_limit' => 4]);

        $nextWeeksGrowthSession = GrowthSession::factory()
            ->create(['date' => $mondayOfNextWeek, 'start_time' => '03:30 pm', 'attendee_limit' => 4]);

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson(route(
            'growth_sessions.week',
            ['date' => $saturdayOfThisWeek->toDateString()]
        ))
            ->assertSee($nextWeeksGrowthSession->date->toDateString())
            ->assertDontSee($thisWeeksGrowthSession->date->toDateString());
    }

    public function test_it_does_not_show_vehikl_only_growth_sessions_of_a_specified_week_for_anonymous_user()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        GrowthSession::factory()->create([
            'date' => $monday,
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false,
        ]);
        GrowthSession::factory()->create([
            'date' => $monday->addDays(1),
            'start_time' => '04:30 pm',
            'attendee_limit' => 4,
            'is_public' => false,
        ]);
        GrowthSession::factory()->create([
            'date' => $monday->addDays(2),
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false,
        ]);
        GrowthSession::factory()->create([
            'date' => $monday->addDays(3),
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false,
        ]);
        GrowthSession::factory()->create([
            'date' => $monday->addDays(4),
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false,
        ]);

        $response = $this->getJson(route('growth_sessions.week'));

        $response->assertSuccessful()
            ->assertExactJson([
                $monday->toDateString() => [],
                $monday->addDays(1)->toDateString() => [],
                $monday->addDays(2)->toDateString() => [],
                $monday->addDays(3)->toDateString() => [],
                $monday->addDays(4)->toDateString() => [],
            ]);
    }

    public function test_it_shows_public_growth_sessions_to_anonymous_users()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        GrowthSession::factory()->create(['date' => $monday, 'start_time' => '03:30 pm', 'attendee_limit' => 4]);
        $isPublic = GrowthSession::factory()->create([
            'is_public' => true,
            'date' => $monday->addDays(1),
            'start_time' => '04:30 pm',
            'attendee_limit' => 4,
        ]);

        $response = $this->getJson(route('growth_sessions.week'));

        $response->assertSuccessful()
            ->assertJsonFragment(['id' => $isPublic->id]);
    }

    public function test_it_provides_a_summary_of_the_growth_sessions_of_the_day()
    {
        $today = '2020-01-02';
        $tomorrow = '2020-01-03';
        $this->setTestNow($today);
        /** @var User $user */
        $user = User::factory()->create();

        $todayGrowthSessions = GrowthSession::factory()->times(2)->create(['date' => $today, 'attendee_limit' => 4]);
        GrowthSession::factory()->times(2)->create(['date' => $tomorrow, 'attendee_limit' => 4]);

        $this->actingAs($user);
        $expectedResponse = $todayGrowthSessions->map(fn (GrowthSession $growthSession) => $this->resourceArray($growthSession))->all();

        $this->getJson(route('growth_sessions.day'))
            ->assertJson($expectedResponse);
    }

    public function test_vehikl_users_can_view_private_growth_sessions()
    {
        $this->setTestNow('2020-01-15');

        $vehiklMember = User::factory()->vehiklMember()->create();
        $monday = CarbonImmutable::parse('Last Monday');
        $vehiklOnlySession = GrowthSession::factory()->create([
            'date' => $monday,
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false,
        ]);
        GrowthSession::factory()->create([
            'is_public' => true,
            'date' => $monday->addDays(1),
            'start_time' => '04:30 pm',
            'attendee_limit' => 4,
        ]);

        $response = $this->actingAs($vehiklMember)->getJson(route('growth_sessions.week'));

        $response->assertSuccessful()
            ->assertJsonFragment(['id' => $vehiklOnlySession->id]);
    }
}
