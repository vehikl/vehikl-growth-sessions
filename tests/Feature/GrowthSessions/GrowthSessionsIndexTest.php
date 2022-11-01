<?php

namespace Tests\Feature\GrowthSessions;


use App\GrowthSession;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class GrowthSessionsIndexTest extends TestCase
{
    public function testItCanProvideAllGrowthSessionsOfTheCurrentWeekForAuthenticatedUser()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');

        $mondayGrowthSession = GrowthSession::factory()
            ->create(['date' => $monday, 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $lateWednesdayGrowthSession = GrowthSession::factory()
            ->create(['date' => $monday->addDays(2), 'start_time' => '04:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $earlyWednesdayGrowthSession = GrowthSession::factory()
            ->create(['date' => $monday->addDays(2), 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $fridayGrowthSession = GrowthSession::factory()
            ->create(['date' => $monday->addDays(4), 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        GrowthSession::factory()
            ->create([
                'date' => $monday->addDays(8),
                'start_time' => '03:30 pm',
                'attendee_limit' => 4
            ]); // GrowthSessions on another week

        $expectedResponse = [
            $monday->toDateString() => [$mondayGrowthSession],
            $monday->addDays(1)->toDateString() => [],
            $monday->addDays(2)->toDateString() => [$earlyWednesdayGrowthSession, $lateWednesdayGrowthSession],
            $monday->addDays(3)->toDateString() => [],
            $monday->addDays(4)->toDateString() => [$fridayGrowthSession],
        ];

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('growth_sessions.week'));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function testItCanProvideAllGrowthSessionsOfTheCurrentWeekForAuthenticatedUserEvenOnFridays()
    {
        $this->setTestNow('Next Friday');

        $mondayGrowthSession = GrowthSession::factory()
            ->create(['date' => Carbon::parse('Last Monday'), 'attendee_limit' => 4])
            ->toArray();
        $fridayGrowthSession = GrowthSession::factory()
            ->create(['date' => today(), 'attendee_limit' => 4])
            ->toArray();

        $expectedResponse = [
            Carbon::parse('Last Monday')->toDateString() => [$mondayGrowthSession],
            Carbon::parse('Last Tuesday')->toDateString() => [],
            Carbon::parse('Last Wednesday')->toDateString() => [],
            Carbon::parse('Last Thursday')->toDateString() => [],
            today()->toDateString() => [$fridayGrowthSession],
        ];

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('growth_sessions.week'));
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
            ->create(['date' => $mondayOfWeekWithGrowthSessions, 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $lateWednesdayGrowthSession = GrowthSession::factory()
            ->create([
                'date' => $mondayOfWeekWithGrowthSessions->addDays(2),
                'start_time' => '04:30 pm',
                'attendee_limit' => 4
            ])
            ->toArray();
        $earlyWednesdayGrowthSession = GrowthSession::factory()
            ->create([
                'date' => $mondayOfWeekWithGrowthSessions->addDays(2),
                'start_time' => '03:30 pm',
                'attendee_limit' => 4
            ])
            ->toArray();
        $fridayGrowthSession = GrowthSession::factory()
            ->create([
                'date' => $mondayOfWeekWithGrowthSessions->addDays(4),
                'start_time' => '03:30 pm',
                'attendee_limit' => 4
            ])
            ->toArray();

        $expectedResponse = [
            $mondayOfWeekWithGrowthSessions->toDateString() => [$mondayGrowthSession],
            $mondayOfWeekWithGrowthSessions->addDays(1)->toDateString() => [],
            $mondayOfWeekWithGrowthSessions->addDays(2)->toDateString() => [
                $earlyWednesdayGrowthSession,
                $lateWednesdayGrowthSession
            ],
            $mondayOfWeekWithGrowthSessions->addDays(3)->toDateString() => [],
            $mondayOfWeekWithGrowthSessions->addDays(4)->toDateString() => [$fridayGrowthSession],
        ];

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route(
            'growth_sessions.week',
            ['date' => $weekThatHasTheGrowthSessions]
        ));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
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

        $response = $this->actingAs($user)->getJson(route('growth_sessions.day'));

        $response->assertJson($todayGrowthSessions->toArray());
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
