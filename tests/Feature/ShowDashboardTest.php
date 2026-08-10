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

    public function testItPagesTenSessionsAtATimeAndReportsTheTotal()
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
                ->has('hosted_sessions.data', 10)
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
                ->has('hosted_sessions.data', 7)
                ->where('hosted_sessions.current_page', 2)
                ->where('hosted_sessions.data.0.title', 'Session 11')
                ->where('hosted_sessions.data.6.title', 'Session 17')
            );
    }

    public function testEachRowCarriesOnlyTheFieldsTheTableRenders()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        $session = $this->hostedBy($host, today(), 'Vue Testing Deep Dive');
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
                ->where('summary.sessions_attended_count', 1)
                ->where('summary.upcoming_count', 1)
            );
    }

    public function testTheGrowthTimeSumsTheSessionsTheUserWasInTheRoomFor()
    {
        $this->setTestNowToASafeWednesday();
        $user = User::factory()->vehiklMember()->create();

        $this->hostedBy($user, today()->subWeek(), 'Ran this one', $this->window('10:00:00', '11:30:00'));
        $this->attendedBy($user, today()->subDay(), 'Joined this one', $this->window('13:00:00', '14:15:00'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('summary.growth_minutes_count', 165));
    }

    /**
     * Time already spent, which is the whole claim the tile makes: a session on the calendar for
     * next week has not grown anybody yet, and watching from the sidelines is not the same as
     * being in the mob — the line `mob_squad` and `yet_to_mob_with` already draw.
     */
    public function testTheGrowthTimeCountsNeitherUpcomingNorWatchedSessions()
    {
        $this->setTestNowToASafeWednesday();
        $user = User::factory()->vehiklMember()->create();

        $this->hostedBy($user, today()->addWeek(), 'Still to come', $this->window('10:00:00', '12:00:00'));
        $this->watchedBy($user, today()->subDay(), 'Only watched', $this->window('10:00:00', '12:00:00'));
        $this->hostedBy($user, today(), 'Today counts', $this->window('09:00:00', '09:30:00'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('summary.growth_minutes_count', 30));
    }

    /**
     * All three roles share one pivot table, so the attended total has to be the attendee role
     * alone: counting the relationship unconstrained would fold in every session they ran or watched.
     */
    public function testTheAttendedTotalCountsNeitherHostedNorWatchedSessions()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        $this->attendedBy($host, today()->subDay(), 'Joined this one');
        $this->attendedBy($host, today()->subDays(2), 'And this one');
        $this->hostedBy($host, today(), 'Ran this one');
        $this->watchedBy($host, today()->subDays(3), 'Only watched this one');

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('summary.sessions_attended_count', 2));
    }

    public function testTheSummaryIsZeroedForAUserWhoHasHostedNothing()
    {
        $this->actingAs(User::factory()->vehiklMember()->create())
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('summary.sessions_hosted_count', 0)
                ->where('summary.sessions_attended_count', 0)
                ->where('summary.upcoming_count', 0)
                ->where('summary.growth_minutes_count', 0)
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

    public function testItReturnsTheCurrentUsersYetToMobWithListForAllTime()
    {
        $this->setTestNowToASafeWednesday();

        [$host, $attendee, $nonParticipant] = User::factory()->vehiklMember()->count(3)
            ->sequence(['name' => 'Host'], ['name' => 'Attendee'], ['name' => 'Non-Participant'])
            ->create(['is_visible_in_statistics' => true]);

        $this->hostedBy($host, today()->subDay(), 'Mobbed together')
            ->attendees()
            ->attach($attendee->id, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('yet_to_mob_with', 1)
                ->where('yet_to_mob_with.0.id', $nonParticipant->id)
                ->where('yet_to_mob_with.0.name', $nonParticipant->name)
            );
    }

    public function testEachMemberYetToBeMobbedWithCarriesTheirAvatar()
    {
        $this->setTestNowToASafeWednesday();

        [$host, $attendee, $nonParticipant] = User::factory()->vehiklMember()->count(3)
            ->sequence(
                ['name' => 'Host'],
                ['name' => 'Attendee'],
                ['name' => 'Non-Participant', 'avatar' => 'https://example.test/nobody.png'],
            )
            ->create(['is_visible_in_statistics' => true]);

        $this->hostedBy($host, today()->subDay(), 'Mobbed together')
            ->attendees()
            ->attach($attendee->id, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('yet_to_mob_with.0', fn (AssertableInertia $member) => $member
                    ->where('id', $nonParticipant->id)
                    ->where('name', 'Non-Participant')
                    ->where('avatar', 'https://example.test/nobody.png')
                )
            );
    }

    public function testTheYetToMobWithListIsNotLimitedToTheCurrentWeek()
    {
        $this->setTestNowToASafeWednesday();

        [$host, $attendee] = User::factory()->vehiklMember()->count(2)
            ->sequence(['name' => 'Host'], ['name' => 'Attendee'])
            ->create(['is_visible_in_statistics' => true]);

        // Their only session together was well before the current week.
        $this->hostedBy($host, today()->subDays(30), 'Mobbed together, a while ago')
            ->attendees()
            ->attach($attendee->id, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('yet_to_mob_with', 0));
    }

    public function testItSortsByDateNewestFirstByDefault()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        $this->hostedBy($host, today()->subDays(2), 'Older');
        $this->hostedBy($host, today()->subDay(), 'Newer');

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sort', 'date')
                ->where('hosted_sessions.data.0.title', 'Newer')
                ->where('hosted_sessions.data.1.title', 'Older')
            );
    }

    public function testItSortsByNameAlphabetically()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        // Ordered so that the newest session is last alphabetically: date must not win.
        $this->hostedBy($host, today()->subDays(2), 'Alpha');
        $this->hostedBy($host, today()->subDay(), 'Zulu');

        $this->actingAs($host)
            ->get(route('dashboard', ['sort' => 'name']))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sort', 'name')
                ->where('hosted_sessions.data.0.title', 'Alpha')
                ->where('hosted_sessions.data.1.title', 'Zulu')
            );
    }

    public function testItSortsByAttendeeCountBusiestFirst()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        // The busier session is the older one, so date cannot be what puts it on top.
        $busy = $this->hostedBy($host, today()->subDays(2), 'Three came');
        $busy->attendees()->attach(User::factory()->count(3)->create()->pluck('id'), ['user_type_id' => UserType::ATTENDEE_ID]);
        $this->hostedBy($host, today()->subDay(), 'Nobody came');

        $this->actingAs($host)
            ->get(route('dashboard', ['sort' => 'attendees']))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sort', 'attendees')
                ->where('hosted_sessions.data.0.title', 'Three came')
                ->where('hosted_sessions.data.1.title', 'Nobody came')
            );
    }

    public function testTheAttendeeSortBreaksTiesByDateDescending()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        $this->hostWithAttendees($host, today()->subDays(2), 'Older, two came', 2);
        $this->hostWithAttendees($host, today(), 'Newer, two came', 2);

        $this->actingAs($host)
            ->get(route('dashboard', ['sort' => 'attendees']))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('hosted_sessions.data.0.title', 'Newer, two came')
                ->where('hosted_sessions.data.1.title', 'Older, two came')
            );
    }

    public function testAnUnrecognisedSortFallsBackToTheDefault()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        $this->hostedBy($host, today()->subDays(2), 'Older');
        $this->hostedBy($host, today()->subDay(), 'Newer');

        $this->actingAs($host)
            ->get(route('dashboard', ['sort' => 'attendee_count; drop table users']))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sort', 'date')
                ->where('hosted_sessions.data.0.title', 'Newer')
            );
    }

    public function testThePaginationLinksCarryTheSortForward()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        foreach (range(1, 12) as $daysAgo) {
            $this->hostedBy($host, today()->subDays($daysAgo), "Session $daysAgo");
        }

        $this->actingAs($host)
            ->get(route('dashboard', ['sort' => 'name']))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('hosted_sessions.next_page_url', fn (?string $url) => str_contains((string) $url, 'sort=name'))
            );
    }

    /**
     * The sort has to apply to the whole history before it is sliced, not to the page once cut:
     * ordering a page of the date-ordered history would leave page two holding the wrong sessions.
     */
    public function testASortedListPagesThroughTheWholeHistoryInThatOrder()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        // Named so alphabetical order is the exact reverse of date order.
        foreach (range(1, 12) as $daysAgo) {
            $this->hostedBy($host, today()->subDays($daysAgo), sprintf('Session %02d', 13 - $daysAgo));
        }

        $this->actingAs($host)
            ->get(route('dashboard', ['sort' => 'name', 'page' => 2]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('hosted_sessions.data', 2)
                ->where('hosted_sessions.data.0.title', 'Session 11')
                ->where('hosted_sessions.data.1.title', 'Session 12')
            );
    }

    public function testItRanksTheTagsTheUserHostsUnderMostOftenFirst()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        $this->hostTaggedSession($host, today()->subDay(), 'Vue', 'Testing');
        $this->hostTaggedSession($host, today()->subDays(2), 'Vue', 'Testing');
        $this->hostTaggedSession($host, today()->subDays(3), 'Vue');

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('top_tags', 2)
                ->where('top_tags.0.name', 'Vue')
                ->where('top_tags.0.sessions_count', 3)
                ->where('top_tags.1.name', 'Testing')
                ->where('top_tags.1.sessions_count', 2)
            );
    }

    public function testItReportsAtMostFiveTags()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        // Six distinct tags, each used once more than the next, so the sixth is the one dropped.
        foreach (['A' => 6, 'B' => 5, 'C' => 4, 'D' => 3, 'E' => 2, 'F' => 1] as $name => $uses) {
            for ($use = 0; $use < $uses; $use++) {
                $this->hostTaggedSession($host, today()->subDay(), $name);
            }
        }

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('top_tags', 5)
                ->where('top_tags.0.name', 'A')
                ->where('top_tags.4.name', 'E')
            );
    }

    public function testTagsOnSessionsTheUserDidNotHostAreNotCounted()
    {
        $this->setTestNowToASafeWednesday();
        $host = User::factory()->vehiklMember()->create();

        $this->hostTaggedSession($host, today()->subDay(), 'Mine');
        $this->attendedBy($host, today()->subDays(2), 'Merely attended')
            ->tags()
            ->attach($this->tag('Theirs'));

        $this->actingAs($host)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('top_tags', 1)
                ->where('top_tags.0.name', 'Mine')
            );
    }

    public function testTheTopTagsAreEmptyForAUserWhoHasNeverTaggedASession()
    {
        $this->actingAs(User::factory()->vehiklMember()->create())
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('top_tags', 0));
    }

    public function testItRanksTheCoWorkersTheUserMobsWithMostOftenFirst()
    {
        $this->setTestNowToASafeWednesday();
        [$user, $regular, $occasional] = User::factory()->vehiklMember()->count(3)
            ->sequence(['name' => 'Me'], ['name' => 'Regular'], ['name' => 'Occasional'])
            ->create();

        $this->mobbedTogether($user, today()->subDay(), $regular, $occasional);
        $this->mobbedTogether($user, today()->subDays(2), $regular);
        $this->mobbedTogether($user, today()->subDays(3), $regular);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('mob_squad', 2)
                ->where('mob_squad.0.id', $regular->id)
                ->where('mob_squad.0.name', 'Regular')
                ->where('mob_squad.0.sessions_together_count', 3)
                ->where('mob_squad.1.name', 'Occasional')
                ->where('mob_squad.1.sessions_together_count', 1)
            );
    }

    public function testEachCoWorkerCarriesTheAvatarTheLeaderboardRenders()
    {
        $this->setTestNowToASafeWednesday();
        [$user, $peer] = User::factory()->vehiklMember()->count(2)
            ->sequence(['name' => 'Me'], ['name' => 'Peer', 'avatar' => 'https://example.test/peer.png'])
            ->create();

        $this->mobbedTogether($user, today()->subDay(), $peer);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('mob_squad.0', fn (AssertableInertia $member) => $member
                    ->where('id', $peer->id)
                    ->where('name', 'Peer')
                    ->where('avatar', 'https://example.test/peer.png')
                    ->where('sessions_together_count', 1)
                )
            );
    }

    /**
     * Guests are anonymised everywhere a Growth Session is rendered, so a leaderboard that named
     * them would be the one place their real name leaked.
     */
    public function testGuestsAreNotCoWorkers()
    {
        $this->setTestNowToASafeWednesday();
        $user = User::factory()->vehiklMember()->create(['name' => 'Me']);
        $guest = User::factory()->vehiklMember(false)->create(['name' => 'Guest In The Room']);

        $this->mobbedTogether($user, today()->subDay(), $guest);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('mob_squad', 0));
    }

    public function testTheMobSquadCountsSessionsTheUserJoinedAsWellAsOnesTheyRan()
    {
        $this->setTestNowToASafeWednesday();
        [$user, $peer] = User::factory()->vehiklMember()->count(2)
            ->sequence(['name' => 'Me'], ['name' => 'Peer'])
            ->create();

        $this->mobbedTogether($user, today()->subDay(), $peer);
        $this->mobbedTogether($peer, today()->subDays(2), $user);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('mob_squad', 1)
                ->where('mob_squad.0.name', 'Peer')
                ->where('mob_squad.0.sessions_together_count', 2)
            );
    }

    public function testTheMobSquadNeverListsTheUserThemselves()
    {
        $this->setTestNowToASafeWednesday();
        [$user, $peer] = User::factory()->vehiklMember()->count(2)
            ->sequence(['name' => 'Me'], ['name' => 'Peer'])
            ->create();

        $this->mobbedTogether($user, today()->subDay(), $peer);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('mob_squad', 1)
                ->where('mob_squad.0.id', $peer->id)
            );
    }

    /**
     * Watching is not mobbing, so neither end of the pair may reach the leaderboard through it.
     * The "yet to mob with" list below it draws the same line, and one Dashboard cannot call the
     * same pairing a mob in one section and an absent one in the other.
     */
    public function testWatchingASessionMobsWithNobody()
    {
        $this->setTestNowToASafeWednesday();
        [$user, $watcher] = User::factory()->vehiklMember()->count(2)
            ->sequence(['name' => 'Me'], ['name' => 'Watcher'])
            ->create();

        $this->hostedBy($user, today()->subDay(), 'They only watched')
            ->watchers()
            ->attach($watcher->id, ['user_type_id' => UserType::WATCHER_ID]);

        $this->watchedBy($user, today()->subDays(2), 'I only watched');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('mob_squad', 0));
    }

    /**
     * The leaderboard and the "yet to mob with" list beneath it read the same view, so a pairing
     * the matrix refuses to call a mob must not be counted as one a few pixels higher up.
     */
    public function testARoomTooBigToBeAMobIsNotCountedAsOne()
    {
        $this->setTestNowToASafeWednesday();
        [$user, $peer] = User::factory()->vehiklMember()->count(2)
            ->sequence(['name' => 'Me'], ['name' => 'Peer'])
            ->create();

        // The pair plus enough others to reach the size the statistics matrix stops calling a mob.
        $crowd = User::factory()->vehiklMember()->count(config('statistics.max_mob_size') - 2)->create();
        $this->mobbedTogether($user, today()->subDay(), $peer, ...$crowd->all());

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('mob_squad', 0));
    }

    public function testASessionStillToComeIsNotAMobYet()
    {
        $this->setTestNowToASafeWednesday();
        [$user, $peer] = User::factory()->vehiklMember()->count(2)
            ->sequence(['name' => 'Me'], ['name' => 'Peer'])
            ->create();

        $this->mobbedTogether($user, today()->subDay(), $peer);
        $this->mobbedTogether($user, today()->addWeek(), $peer);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('mob_squad', 1)
                ->where('mob_squad.0.sessions_together_count', 1)
            );
    }

    public function testSomebodyHiddenFromStatisticsIsNotListed()
    {
        $this->setTestNowToASafeWednesday();
        $user = User::factory()->vehiklMember()->create(['name' => 'Me']);
        $hidden = User::factory()->vehiklMember()->create(['name' => 'Hidden', 'is_visible_in_statistics' => false]);

        $this->mobbedTogether($user, today()->subDay(), $hidden);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('mob_squad', 0));
    }

    public function testItReportsAtMostFiveCoWorkers()
    {
        $this->setTestNowToASafeWednesday();
        $user = User::factory()->vehiklMember()->create();

        // Six peers, each mobbed with one time more than the next, so the sixth is the one dropped.
        foreach (['A' => 6, 'B' => 5, 'C' => 4, 'D' => 3, 'E' => 2, 'F' => 1] as $name => $mobs) {
            $peer = User::factory()->vehiklMember()->create(['name' => $name]);

            for ($mob = 0; $mob < $mobs; $mob++) {
                $this->mobbedTogether($user, today()->subDay(), $peer);
            }
        }

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('mob_squad', 5)
                ->where('mob_squad.0.name', 'A')
                ->where('mob_squad.4.name', 'E')
            );
    }

    public function testCoWorkersMobbedWithEquallyOftenAreListedAlphabetically()
    {
        $this->setTestNowToASafeWednesday();
        [$user, $zulu, $alpha] = User::factory()->vehiklMember()->count(3)
            ->sequence(['name' => 'Me'], ['name' => 'Zulu'], ['name' => 'Alpha'])
            ->create();

        $this->mobbedTogether($user, today()->subDay(), $zulu, $alpha);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('mob_squad.0.name', 'Alpha')
                ->where('mob_squad.1.name', 'Zulu')
            );
    }

    public function testTheMobSquadIsEmptyForAUserWhoHasNeverMobbed()
    {
        $this->setTestNowToASafeWednesday();
        $user = User::factory()->vehiklMember()->create();

        $this->hostedBy($user, today()->subDay(), 'Nobody came');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('mob_squad', 0));
    }

    /** A Growth Session $host ran with each of $peers in the room. */
    private function mobbedTogether(User $host, CarbonInterface $date, User ...$peers): GrowthSession
    {
        $session = $this->hostedBy($host, $date, 'Mobbed together');
        $session->attendees()->attach(
            collect($peers)->pluck('id'),
            ['user_type_id' => UserType::ATTENDEE_ID]
        );

        return $session;
    }

    private function hostTaggedSession(User $host, CarbonInterface $date, string ...$tagNames): GrowthSession
    {
        $session = $this->hostedBy($host, $date, 'Tagged session');
        $session->tags()->attach(array_map(fn (string $name) => $this->tag($name), $tagNames));

        return $session;
    }

    /** Tags are shared across sessions, so the same name must resolve to the same row. */
    private function tag(string $name): Tag
    {
        return Tag::firstOrCreate(['name' => $name]);
    }

    private function hostedBy(User $host, CarbonInterface $date, string $title, array $attributes = []): GrowthSession
    {
        return GrowthSession::factory()
            ->hasAttached($host, ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create([...$attributes, 'date' => $date, 'title' => $title]);
    }

    private function hostWithAttendees(User $host, CarbonInterface $date, string $title, int $attendees): GrowthSession
    {
        $session = $this->hostedBy($host, $date, $title);

        if ($attendees > 0) {
            $session->attendees()->attach(
                User::factory()->count($attendees)->create()->pluck('id'),
                ['user_type_id' => UserType::ATTENDEE_ID]
            );
        }

        return $session;
    }

    private function attendedBy(User $attendee, CarbonInterface $date, string $title, array $attributes = []): GrowthSession
    {
        return GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember()->create(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->hasAttached($attendee, ['user_type_id' => UserType::ATTENDEE_ID], 'attendees')
            ->create([...$attributes, 'date' => $date, 'title' => $title]);
    }

    private function watchedBy(User $watcher, CarbonInterface $date, string $title, array $attributes = []): GrowthSession
    {
        return GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember()->create(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->hasAttached($watcher, ['user_type_id' => UserType::WATCHER_ID], 'watchers')
            ->create([...$attributes, 'date' => $date, 'title' => $title]);
    }

    /** The scheduled window of a session, as the factory takes it. */
    private function window(string $startTime, string $endTime): array
    {
        return ['start_time' => $startTime, 'end_time' => $endTime];
    }
}
