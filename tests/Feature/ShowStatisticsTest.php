<?php

namespace Tests\Feature;

use App\Models\GrowthSession;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserType;
use Carbon\CarbonInterface;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ShowStatisticsTest extends TestCase
{
    public function testItIsAccessibleByVehikaliens()
    {
        $this->actingAs(User::factory()->vehiklMember()->create())
            ->get(route('statistics.index'))
            ->assertSuccessful();
    }

    public function testGuestsCannotAccessStatistics()
    {
        $this->get(route('statistics.index'))
            ->assertRedirect('/');
    }

    public function testNonVehikaliensCannotAccessStatistics()
    {
        $this->actingAs(User::factory()->vehiklMember(false)->create())
            ->get(route('statistics.index'))
            ->assertForbidden();
    }

    public function testItReturnsAWeeklySummary()
    {
        [$owner] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs($owner)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Statistics', false)
                ->where('summary.lifetime_sessions_count', 5)
                ->where('summary.sessions_this_week_count', 2)
                ->where('summary.weekly_unique_participants_count', 2)
                ->has('summary.average_attendance_count')
            );
    }

    public function testItRanksThisWeeksTopHosts()
    {
        $this->setTestNowToASafeWednesday();

        [$prolificHost, $occasionalHost, $nonHost] = User::factory()->vehiklMember()->count(3)
            ->sequence(['name' => 'Prolific'], ['name' => 'Occasional'], ['name' => 'Non-Host'])
            ->create(['is_visible_in_statistics' => true]);

        $this->makeGrowthSessionOwnedBy($prolificHost, today());
        $this->makeGrowthSessionOwnedBy($prolificHost, today()->subDay());
        $this->makeGrowthSessionOwnedBy($occasionalHost, today());
        $this->makeGrowthSessionOwnedBy($nonHost, today()->subDays(7)); // last week, out of range

        $this->actingAs($prolificHost)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('top_hosts', 2)
                ->where('top_hosts.0.name', 'Prolific')
                ->where('top_hosts.0.sessions_hosted_count', 2)
                ->where('top_hosts.1.name', 'Occasional')
                ->where('top_hosts.1.sessions_hosted_count', 1)
            );
    }

    public function testEachTopHostCarriesTheAvatarTheListRenders()
    {
        $this->setTestNowToASafeWednesday();

        $host = User::factory()->vehiklMember()->create([
            'name' => 'Prolific',
            'avatar' => 'https://example.test/prolific.png',
            'is_visible_in_statistics' => true,
        ]);

        $this->makeGrowthSessionOwnedBy($host, today());

        $this->actingAs($host)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('top_hosts.0.name', 'Prolific')
                ->where('top_hosts.0.avatar', 'https://example.test/prolific.png')
            );
    }

    public function testItReturnsTagUsageCountsForTheCurrentWeek()
    {
        $this->setTestNowToASafeWednesday();
        $user = User::factory()->vehiklMember()->create();

        $laravel = Tag::factory()->create(['name' => 'Laravel']);
        $vue = Tag::factory()->create(['name' => 'Vue']);
        $unused = Tag::factory()->create(['name' => 'Unused']);

        GrowthSession::factory()->count(2)->create(['date' => today()])
            ->each(fn (GrowthSession $session) => $session->tags()->attach($laravel));
        GrowthSession::factory()->create(['date' => today()])->tags()->attach($vue);
        GrowthSession::factory()->create(['date' => today()->subDays(7)])->tags()->attach($unused);

        $this->actingAs($user)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('tags', 2)
                ->where('tags.0.name', 'Laravel')
                ->where('tags.0.sessions_count', 2)
                ->where('tags.1.name', 'Vue')
                ->where('tags.1.sessions_count', 1)
            );
    }

    private function makeGrowthSessionOwnedBy(User $owner, CarbonInterface $date): void
    {
        GrowthSession::factory()
            ->hasAttached($owner, ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['date' => $date]);
    }

    private function makeGrowthSessionWithSingleAttendee(
        User $attendee,
        User $owner,
        CarbonInterface $date
    ): void
    {
        GrowthSession::factory()
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
