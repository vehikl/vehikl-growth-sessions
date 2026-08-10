<?php

namespace Tests\Feature\GrowthSessions;


use App\Http\Resources\GrowthSession as GrowthSessionResource;
use App\Models\GrowthSession;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class GrowthSessionsIndexTest extends TestCase
{
    /**
     * Builds the exact payload the API would produce for $growthSession as seen by the currently
     * `actingAs` user (or a guest, if none) - call this after `actingAs` so redaction rules
     * (e.g. hidden location) match what the real endpoint will return.
     */
    private function resourceArray(GrowthSession $growthSession): array
    {
        $resource = new GrowthSessionResource(
            $growthSession->fresh(['attendees', 'watchers', 'comments', 'anydesk', 'tags'])
        );

        // toArray() embeds nested resources/collections (comments, tags) as objects rather than plain
        // arrays - a round trip through json_encode/decode resolves them the same way the real HTTP
        // response would, so this matches what assertJson() actually compares against.
        return json_decode(json_encode($resource->toArray(request())), true);
    }

    public function testItCanProvideAllGrowthSessionsOfTheCurrentWeekForAuthenticatedUser()
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
                'attendee_limit' => 4
            ]); // GrowthSessions on another week

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $expectedResponse = [
            $monday->toDateString() => [$this->resourceArray($mondayGrowthSession)],
            $monday->addDays(1)->toDateString() => [],
            $monday->addDays(2)->toDateString() => [
                $this->resourceArray($earlyWednesdayGrowthSession),
                $this->resourceArray($lateWednesdayGrowthSession)
            ],
            $monday->addDays(3)->toDateString() => [],
            $monday->addDays(4)->toDateString() => [
                $this->resourceArray($fridayGrowthSession)
            ],
        ];

        $this->getJson(route('growth_sessions.week'))
            ->assertSuccessful()
            ->assertJson($expectedResponse);
    }

    public function testItCanProvideAllGrowthSessionsOfTheCurrentWeekForAuthenticatedUserEvenOnFridays()
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

    public function testItCanProvideAllGrowthSessionsOfASpecifiedWeekForAuthenticatedUserIfADateIsGiven()
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
                'attendee_limit' => 4
            ]);
        $earlyWednesdayGrowthSession = GrowthSession::factory()
            ->create([
                'date' => $mondayOfWeekWithGrowthSessions->addDays(2),
                'start_time' => '03:30 pm',
                'attendee_limit' => 4
            ]);
        $fridayGrowthSession = GrowthSession::factory()
            ->create([
                'date' => $mondayOfWeekWithGrowthSessions->addDays(4),
                'start_time' => '03:30 pm',
                'attendee_limit' => 4
            ]);

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $expectedResponse = [
            $mondayOfWeekWithGrowthSessions->toDateString() => [$this->resourceArray($mondayGrowthSession)],
            $mondayOfWeekWithGrowthSessions->addDays(1)->toDateString() => [],
            $mondayOfWeekWithGrowthSessions->addDays(2)->toDateString() => [
                $this->resourceArray($earlyWednesdayGrowthSession),
                $this->resourceArray($lateWednesdayGrowthSession)
            ],
            $mondayOfWeekWithGrowthSessions->addDays(3)->toDateString() => [],
            $mondayOfWeekWithGrowthSessions->addDays(4)->toDateString() => [
                $this->resourceArray($fridayGrowthSession)
            ],
        ];

        $response = $this->getJson(route(
            'growth_sessions.week',
            ['date' => $weekThatHasTheGrowthSessions]
        ));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function testItShowsNextWeeksGrowthSessionsIfSpecifiedDateFallsOnWeekend()
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

    public function testItDoesNotShowVehiklOnlyGrowthSessionsOfASpecifiedWeekForAnonymousUser()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        GrowthSession::factory()->create([
            'date' => $monday,
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false
        ]);
        GrowthSession::factory()->create([
            'date' => $monday->addDays(1),
            'start_time' => '04:30 pm',
            'attendee_limit' => 4,
            'is_public' => false
        ]);
        GrowthSession::factory()->create([
            'date' => $monday->addDays(2),
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false
        ]);
        GrowthSession::factory()->create([
            'date' => $monday->addDays(3),
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false
        ]);
        GrowthSession::factory()->create([
            'date' => $monday->addDays(4),
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false
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

    public function testItShowsPublicGrowthSessionsToAnonymousUsers()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        GrowthSession::factory()->create(['date' => $monday, 'start_time' => '03:30 pm', 'attendee_limit' => 4]);
        $isPublic = GrowthSession::factory()->create([
            'is_public' => true,
            'date' => $monday->addDays(1),
            'start_time' => '04:30 pm',
            'attendee_limit' => 4
        ]);

        $response = $this->getJson(route('growth_sessions.week'));

        $response->assertSuccessful()
            ->assertJsonFragment(['id' => $isPublic->id]);
    }

    public function testItProvidesASummaryOfTheGrowthSessionsOfTheDay()
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

    public function testVehiklUsersCanViewPrivateGrowthSessions()
    {
        $this->setTestNow('2020-01-15');

        $vehiklMember = User::factory()->vehiklMember()->create();
        $monday = CarbonImmutable::parse('Last Monday');
        $vehiklOnlySession = GrowthSession::factory()->create([
            'date' => $monday,
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false
        ]);
        GrowthSession::factory()->create([
            'is_public' => true,
            'date' => $monday->addDays(1),
            'start_time' => '04:30 pm',
            'attendee_limit' => 4
        ]);

        $response = $this->actingAs($vehiklMember)->getJson(route('growth_sessions.week'));

        $response->assertSuccessful()
            ->assertJsonFragment(['id' => $vehiklOnlySession->id]);
    }
}
