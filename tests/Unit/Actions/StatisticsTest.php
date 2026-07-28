<?php

namespace Tests\Unit\Actions;

use App\Actions\Statistics;
use App\Models\GrowthSession;
use App\Models\GrowthSessionUser;
use App\Models\User;
use App\Models\UserType;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    public function testItCountsSessionsHostedAttendedAndWatchedPerUser()
    {
        [$owner, $attendee, $nonParticipant] = $this->setupFiveDaysWorthOfGrowthSessions();

        $statistics = $this->statisticsForLastWeek();

        $this->assertSame([
            'total_sessions_count' => 5,
            'sessions_hosted_count' => 5,
            'sessions_attended_count' => 0,
            'sessions_watched_count' => 0,
        ], $this->countsFor($statistics, $owner));

        $this->assertSame([
            'total_sessions_count' => 5,
            'sessions_hosted_count' => 0,
            'sessions_attended_count' => 5,
            'sessions_watched_count' => 0,
        ], $this->countsFor($statistics, $attendee));

        $this->assertSame([
            'total_sessions_count' => 0,
            'sessions_hosted_count' => 0,
            'sessions_attended_count' => 0,
            'sessions_watched_count' => 0,
        ], $this->countsFor($statistics, $nonParticipant));
    }

    public function testItOmitsUsersWhoOptedOutOfStatistics()
    {
        $this->setupFiveDaysWorthOfGrowthSessions();

        $statistics = $this->statisticsForLastWeek();

        $this->assertNotContains('Opt-out of stats', $statistics->pluck('name')->all());
    }

    public function testItIncludesAListOfPeopleTheyHaveMobbedWithAsAttendees()
    {
        [$owner, $attendee, $nonParticipant] = $this->setupFiveDaysWorthOfGrowthSessions();

        $statistics = $this->statisticsForLastWeek();

        $this->assertSame([['id' => $attendee->id, 'name' => $attendee->name]], $this->mobbedWith($statistics, $owner));
        $this->assertSame([['id' => $owner->id, 'name' => $owner->name]], $this->mobbedWith($statistics, $attendee));
        $this->assertSame([], $this->mobbedWith($statistics, $nonParticipant));
    }

    public function testItAlsoIncludesAListOfPeopleTheyHaveNotMobbedWithAsAttendees()
    {
        [$owner, $attendee, $nonParticipant] = $this->setupFiveDaysWorthOfGrowthSessions();

        $statistics = $this->statisticsForLastWeek();

        $this->assertSame([['id' => $nonParticipant->id, 'name' => $nonParticipant->name]], $this->notMobbedWith($statistics, $owner));
        $this->assertSame([['id' => $nonParticipant->id, 'name' => $nonParticipant->name]], $this->notMobbedWith($statistics, $attendee));
        $this->assertSame([
            ['id' => $owner->id, 'name' => $owner->name],
            ['id' => $attendee->id, 'name' => $attendee->name],
        ], $this->notMobbedWith($statistics, $nonParticipant));
    }

    public function testItDisregardsGrowthSessionsWithMoreAttendeesThanTheMaxMobSize()
    {
        $this->setTestNowToASafeWednesday();

        $owner = User::factory()->vehiklMember()->create(['is_visible_in_statistics' => true]);
        $attendees = User::factory()->vehiklMember()->count(config('statistics.max_mob_size'))->create(['is_visible_in_statistics' => true]);

        $growthSession = GrowthSession::factory()
            ->hasAttached($owner, ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create();

        GrowthSessionUser::query()->insert($attendees->map(fn (User $attendee) => [
            'growth_session_id' => $growthSession->id,
            'user_id' => $attendee->id,
            'user_type_id' => UserType::ATTENDEE_ID,
        ])->toArray());

        $statistics = $this->statisticsForLastWeek();

        $this->assertCount($attendees->count(), $this->notMobbedWith($statistics, $owner));
        $this->assertSame([], $this->mobbedWith($statistics, $owner));
    }

    public function testItAllowsSomeDevelopersToHaveParticipationValidatedEvenOnLargerGrowthSessions()
    {
        $this->setTestNowToASafeWednesday();

        $owner = User::factory()->vehiklMember()->create(['is_visible_in_statistics' => true]);
        $loosenRulesUser = User::factory()->create(['name' => 'Exception', 'is_visible_in_statistics' => true]);
        $otherAttendees = User::factory()->vehiklMember()->count(config('statistics.max_mob_size'))->create(['is_visible_in_statistics' => true]);

        $growthSession = GrowthSession::factory()
            ->hasAttached($owner, ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create();

        GrowthSessionUser::query()->insert($otherAttendees->map(fn (User $attendee) => [
            'growth_session_id' => $growthSession->id,
            'user_id' => $attendee->id,
            'user_type_id' => UserType::ATTENDEE_ID,
        ])->toArray());

        GrowthSessionUser::query()->forceCreate([
            'user_id' => $loosenRulesUser->id,
            'growth_session_id' => $growthSession->id,
            'user_type_id' => UserType::ATTENDEE_ID,
        ]);

        config()->set(['statistics.loosen_participation_rules.user_ids' => [$loosenRulesUser->id]]);

        $statistics = $this->statisticsForLastWeek();

        $this->assertSame([['id' => $loosenRulesUser->id, 'name' => $loosenRulesUser->name]], $this->mobbedWith($statistics, $owner));
    }

    public function testItAllowsSomeDevelopersToHaveParticipationValidatedEvenWhenTheyAttendAsWatchers()
    {
        $this->setTestNowToASafeWednesday();

        $owner = User::factory()->vehiklMember()->create(['is_visible_in_statistics' => true]);
        $loosenRulesUser = User::factory()->create(['name' => 'Exception', 'is_visible_in_statistics' => true]);

        $growthSession = GrowthSession::factory()
            ->hasAttached($owner, ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create();

        GrowthSessionUser::query()->forceCreate([
            'user_id' => $loosenRulesUser->id,
            'growth_session_id' => $growthSession->id,
            'user_type_id' => UserType::WATCHER_ID,
        ]);

        config()->set(['statistics.loosen_participation_rules.user_ids' => [$loosenRulesUser->id]]);

        $statistics = $this->statisticsForLastWeek();

        $this->assertSame([['id' => $loosenRulesUser->id, 'name' => $loosenRulesUser->name]], $this->mobbedWith($statistics, $owner));
    }

    public function testTheDevelopersThatHaveLooseParticipationRulesStillNeedToBeInTheGrowthSessionToCountAsValid()
    {
        $this->setTestNowToASafeWednesday();

        $nonParticipatingUser = User::factory()->vehiklMember()->create([
            'name' => 'Regular', 'is_visible_in_statistics' => true,
        ]);
        $loosenRulesUser = User::factory()->create(['name' => 'Exception', 'is_visible_in_statistics' => true]);

        config()->set(['statistics.loosen_participation_rules.user_ids' => [$loosenRulesUser->id]]);

        $statistics = $this->statisticsForLastWeek();

        $this->assertSame([], $this->mobbedWith($statistics, $nonParticipatingUser));
        $this->assertSame([], $this->mobbedWith($statistics, $loosenRulesUser));
    }

    public function testItOnlyCountsSessionsWithinTheGivenDateRange()
    {
        [$owner, $attendee] = $this->setupFiveDaysWorthOfGrowthSessions();

        $statistics = app(Statistics::class)->getFormattedStatisticsFor(
            today()->subDay()->toDateString(),
            today()->toDateString(),
        );

        $this->assertSame([
            'total_sessions_count' => 1,
            'sessions_hosted_count' => 1,
            'sessions_attended_count' => 0,
            'sessions_watched_count' => 0,
        ], $this->countsFor($statistics, $owner));

        $this->assertSame([
            'total_sessions_count' => 1,
            'sessions_hosted_count' => 0,
            'sessions_attended_count' => 1,
            'sessions_watched_count' => 0,
        ], $this->countsFor($statistics, $attendee));
    }

    private function statisticsForLastWeek(): Collection
    {
        return app(Statistics::class)->getFormattedStatisticsFor(
            today()->subDays(7)->toDateString(),
            today()->toDateString(),
        );
    }

    private function statisticsFor(Collection $statistics, User $user): array
    {
        $userStatistics = $statistics->firstWhere('user_id', $user->id);

        $this->assertNotNull($userStatistics, "Expected statistics for {$user->name}, found none.");

        return $userStatistics;
    }

    private function countsFor(Collection $statistics, User $user): array
    {
        return collect($this->statisticsFor($statistics, $user))
            ->only(['total_sessions_count', 'sessions_hosted_count', 'sessions_attended_count', 'sessions_watched_count'])
            ->all();
    }

    private function mobbedWith(Collection $statistics, User $user): array
    {
        return collect($this->statisticsFor($statistics, $user)['has_mobbed_with'])->all();
    }

    private function notMobbedWith(Collection $statistics, User $user): array
    {
        return collect($this->statisticsFor($statistics, $user)['has_not_mobbed_with'])->all();
    }

    private function makeGrowthSessionWithSingleAttendee(
        User $attendee,
        User $owner,
        CarbonInterface $date
    ): GrowthSession {
        return GrowthSession::factory()
            ->hasAttached($attendee, ['user_type_id' => UserType::ATTENDEE_ID], 'attendees')
            ->hasAttached($owner, ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['date' => $date]);
    }

    private function setupFiveDaysWorthOfGrowthSessions(): array
    {
        $this->setTestNowToASafeWednesday();

        [$owner, $attendee, $nonParticipant] = User::factory()->vehiklMember()->count(4)
            ->sequence(
                ['name' => 'Owner'],
                ['name' => 'Attendee'],
                ['name' => 'Non-Participant'],
                ['name' => 'Opt-out of stats', 'is_visible_in_statistics' => false],
            )
            ->create();

        $this->makeGrowthSessionWithSingleAttendee($attendee, $owner, today()->subDays(1)); // Tuesday
        $this->makeGrowthSessionWithSingleAttendee($attendee, $owner, today()->subDays(2)); // Monday
        $this->makeGrowthSessionWithSingleAttendee($attendee, $owner, today()->subDays(5)); // Last Friday
        $this->makeGrowthSessionWithSingleAttendee($attendee, $owner, today()->subDays(6)); // Last Thursday
        $this->makeGrowthSessionWithSingleAttendee($attendee, $owner, today()->subDays(7)); // Last Wednesday

        return [$owner, $attendee, $nonParticipant];
    }
}
