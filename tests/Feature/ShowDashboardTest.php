<?php

namespace Tests\Feature;

use App\Models\GrowthSession;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserType;
use Carbon\CarbonInterface;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ShowDashboardTest extends TestCase
{
    public function testGuestsAreRedirectedAwayFromTheDashboard()
    {
        $this->get(route('dashboard'))
            ->assertRedirect('/');
    }

    public function testItRendersTheDashboardForASignedInUser()
    {
        $this->actingAs(User::factory()->vehiklMember()->create())
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Dashboard', false));
    }

    public function testItOnlyListsSessionsTheUserHosted()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        $hosted = $this->hostedBy($host, today(), 'Hosted by me');
        $this->attendedBy($host, today()->subDay(), 'Merely attended');
        $this->watchedBy($host, today()->subDays(2), 'Merely watched');
        $this->hostedBy(User::factory()->vehiklMember()->create(), today()->subDays(3), 'Somebody else entirely');

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('hosted_sessions.data', 1)
                ->where('hosted_sessions.data.0.id', $hosted->id)
                ->where('hosted_sessions.data.0.title', 'Hosted by me')
            );
    }

    public function testOneUsersHistoryNeverContainsAnotherUsersSessions()
    {
        $this->setTestNowToASafeWednesday();
        [$host, $otherHost] = User::factory()->vehiklMember()->count(2)->create();

        $this->hostedBy($host, today(), 'Mine');
        $this->hostedBy($otherHost, today(), 'Theirs');

        $this->actingAs($otherHost)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('hosted_sessions.data', 1)
                ->where('hosted_sessions.data.0.title', 'Theirs')
            );
    }

    public function testItOrdersSessionsByDateDescendingThenStartTimeDescending()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        $this->hostedBy($host, today()->subDays(2), 'Older day');
        $this->hostedBy($host, today(), 'Today, morning', ['start_time' => '09:00', 'end_time' => '10:00']);
        $this->hostedBy($host, today(), 'Today, afternoon', ['start_time' => '15:00', 'end_time' => '16:00']);

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('hosted_sessions.data', 3)
                ->where('hosted_sessions.data.0.title', 'Today, afternoon')
                ->where('hosted_sessions.data.1.title', 'Today, morning')
                ->where('hosted_sessions.data.2.title', 'Older day')
            );
    }

    public function testItIncludesUpcomingSessionsAheadOfPastOnes()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        $this->hostedBy($host, today()->subWeek(), 'Last week');
        $this->hostedBy($host, today()->addWeek(), 'Next week');

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('hosted_sessions.data', 2)
                ->where('hosted_sessions.data.0.title', 'Next week')
                ->where('hosted_sessions.data.1.title', 'Last week')
            );
    }

    public function testItPagesFifteenSessionsAtATimeAndReportsTheTotal()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        foreach (range(1, 17) as $daysAgo) {
            $this->hostedBy($host, today()->subDays($daysAgo), "Session $daysAgo");
        }

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('hosted_sessions.data', 15)
                ->where('hosted_sessions.total', 17)
                ->where('hosted_sessions.current_page', 1)
                ->where('hosted_sessions.last_page', 2)
                ->where('hosted_sessions.data.0.title', 'Session 1')
            );
    }

    public function testItReturnsTheRequestedPageOfHistory()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        foreach (range(1, 17) as $daysAgo) {
            $this->hostedBy($host, today()->subDays($daysAgo), "Session $daysAgo");
        }

        $this->actingAs($host)
            ->get(route('dashboard', ['page' => 2]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('hosted_sessions.data', 2)
                ->where('hosted_sessions.current_page', 2)
                ->where('hosted_sessions.data.0.title', 'Session 16')
                ->where('hosted_sessions.data.1.title', 'Session 17')
            );
    }

    public function testEachRowCarriesOnlyTheFieldsTheTableRenders()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        $session = $this->hostedBy($host, today(), 'Vue Testing Deep Dive', [
            'start_time' => '15:30',
            'end_time' => '17:00',
        ]);
        $session->tags()->attach(Tag::factory()->create(['name' => 'Vue']));

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('hosted_sessions.data.0', fn (AssertableInertia $row) => $row
                    ->where('id', $session->id)
                    ->where('title', 'Vue Testing Deep Dive')
                    ->where('date', '2020-01-15')
                    ->where('date_label', 'Jan 15, 2020')
                    ->where('is_upcoming', true)
                    ->where('time_label', '3:30 pm – 5:00 pm')
                    ->where('attendee_count', 0)
                    ->has('tags', 1)
                    ->where('tags.0.name', 'Vue')
                )
            );
    }

    public function testItMarksPastSessionsAsNotUpcoming()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        $this->hostedBy($host, today()->addDay(), 'Tomorrow');
        $this->hostedBy($host, today(), 'Today');
        $this->hostedBy($host, today()->subDay(), 'Yesterday');

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('hosted_sessions.data.0.is_upcoming', true)
                ->where('hosted_sessions.data.1.is_upcoming', true)
                ->where('hosted_sessions.data.2.is_upcoming', false)
            );
    }

    public function testItSummarisesHostingAcrossTheWholeHistory()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        $busy = $this->hostedBy($host, today()->subWeek(), 'Three came');
        $busy->attendees()->attach(User::factory()->count(3)->create()->pluck('id'), ['user_type_id' => UserType::ATTENDEE_ID]);

        $upcoming = $this->hostedBy($host, today()->addWeek(), 'Two are coming');
        $upcoming->attendees()->attach(User::factory()->count(2)->create()->pluck('id'), ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->hostedBy($host, today()->subDay(), 'Nobody came');
        $this->attendedBy($host, today()->subDay(), 'Somebody else hosted this');

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('summary.sessions_hosted_count', 3)
                ->where('summary.upcoming_count', 1)
                ->where('summary.total_attendees_count', 5)
            );
    }

    public function testTheSummaryIsZeroedForAUserWhoHasHostedNothing()
    {
        $this->actingAs(User::factory()->vehiklMember()->create())
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('summary.sessions_hosted_count', 0)
                ->where('summary.upcoming_count', 0)
                ->where('summary.total_attendees_count', 0)
            );
    }

    public function testTheAttendeeCountExcludesTheHost()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        $alone = $this->hostedBy($host, today(), 'Nobody came');
        $popular = $this->hostedBy($host, today()->subDay(), 'Two others came');
        $popular->attendees()->attach(
            User::factory()->count(2)->create()->pluck('id'),
            ['user_type_id' => UserType::ATTENDEE_ID]
        );

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('hosted_sessions.data.0.id', $alone->id)
                ->where('hosted_sessions.data.0.attendee_count', 0)
                ->where('hosted_sessions.data.1.id', $popular->id)
                ->where('hosted_sessions.data.1.attendee_count', 2)
            );
    }

    public function testAUserWhoHasHostedNothingGetsAnEmptyResultSet()
    {
        $this->actingAs(User::factory()->vehiklMember()->create())
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('hosted_sessions.data', 0)
                ->where('hosted_sessions.total', 0)
            );
    }

    public function testANonVehiklMemberReachesTheDashboardAndSeesAnEmptyResultSet()
    {
        $this->actingAs(User::factory()->vehiklMember(false)->create())
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard', false)
                ->has('hosted_sessions.data', 0)
            );
    }

    private function hostedBy(User $host, CarbonInterface $date, string $title, array $attributes = []): GrowthSession
    {
        return GrowthSession::factory()
            ->hasAttached($host, ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create([...$attributes, 'date' => $date, 'title' => $title]);
    }

    private function attendedBy(User $attendee, CarbonInterface $date, string $title): GrowthSession
    {
        return GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember()->create(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->hasAttached($attendee, ['user_type_id' => UserType::ATTENDEE_ID], 'attendees')
            ->create(['date' => $date, 'title' => $title]);
    }

    private function watchedBy(User $watcher, CarbonInterface $date, string $title): GrowthSession
    {
        return GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember()->create(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->hasAttached($watcher, ['user_type_id' => UserType::WATCHER_ID], 'watchers')
            ->create(['date' => $date, 'title' => $title]);
    }
}
