<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\GrowthSession;
use App\Models\Tag;
use App\Models\User;
use Carbon\CarbonInterface;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ShowStatisticsTest extends TestCase
{
    public function test_it_is_accessible_by_vehikaliens()
    {
        $this->actingAs(User::factory()->vehiklMember()->create())
            ->get(route('statistics.index'))
            ->assertSuccessful();
    }

    public function test_guests_cannot_access_statistics()
    {
        $this->get(route('statistics.index'))
            ->assertRedirect('/');
    }

    public function test_non_vehikaliens_cannot_access_statistics()
    {
        $this->actingAs(User::factory()->vehiklMember(false)->create())
            ->get(route('statistics.index'))
            ->assertForbidden();
    }

    public function test_it_returns_a_weekly_summary()
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
                ->has('summary.lifetime_minutes_count')
            );
    }

    public function test_it_sums_every_minute_ever_spent_in_a_growth_session()
    {
        $user = User::factory()->vehiklMember()->create();

        // Two hours, then three and a half: 330 minutes all told.
        GrowthSession::factory()->create(['start_time' => '15:00', 'end_time' => '17:00']);
        GrowthSession::factory()->create(['start_time' => '09:00', 'end_time' => '12:30']);

        $this->actingAs($user)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('summary.lifetime_minutes_count', 330));
    }

    public function test_the_growth_time_counts_no_purely_social_sessions()
    {
        $user = User::factory()->vehiklMember()->create();

        GrowthSession::factory()->create(['start_time' => '15:00', 'end_time' => '17:00']);

        $social = GrowthSession::factory()->create(['start_time' => '17:00', 'end_time' => '19:00']);
        $social->tags()->attach(Tag::firstOrCreate(['name' => GrowthSession::SOCIAL_TAG]));

        $this->actingAs($user)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('summary.lifetime_minutes_count', 120));
    }

    public function test_a_social_session_that_also_carries_a_subject_tag_still_counts_towards_the_growth_time()
    {
        $user = User::factory()->vehiklMember()->create();

        $socialAndTechnical = GrowthSession::factory()->create(['start_time' => '15:00', 'end_time' => '17:00']);
        $socialAndTechnical->tags()->attach([
            Tag::firstOrCreate(['name' => GrowthSession::SOCIAL_TAG])->id,
            Tag::firstOrCreate(['name' => 'Laravel'])->id,
        ]);

        $this->actingAs($user)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('summary.lifetime_minutes_count', 120));
    }

    public function test_the_growth_time_counts_no_lightning_talks()
    {
        $user = User::factory()->vehiklMember()->create();

        GrowthSession::factory()->create(['title' => 'Refactoring workshop', 'start_time' => '15:00', 'end_time' => '17:00']);
        GrowthSession::factory()->create(['title' => 'Lightning Talks', 'start_time' => '12:00', 'end_time' => '13:00']);
        GrowthSession::factory()->create(['title' => 'August Lightning Talks 2026', 'start_time' => '12:00', 'end_time' => '13:00']);

        $this->actingAs($user)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('summary.lifetime_minutes_count', 120));
    }

    public function test_the_lifetime_session_count_still_counts_social_sessions_and_lightning_talks()
    {
        $user = User::factory()->vehiklMember()->create();

        GrowthSession::factory()->create(['title' => 'Refactoring workshop']);
        GrowthSession::factory()->create(['title' => 'Lightning Talks']);

        $social = GrowthSession::factory()->create();
        $social->tags()->attach(Tag::firstOrCreate(['name' => GrowthSession::SOCIAL_TAG]));

        $this->actingAs($user)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('summary.lifetime_sessions_count', 3));
    }

    public function test_it_ranks_this_weeks_top_hosts()
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

    public function test_each_top_host_carries_the_avatar_the_list_renders()
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

    public function test_it_returns_tag_usage_counts_for_the_current_week()
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

    public function test_it_returns_a_row_for_every_statistics_visible_member()
    {
        [$owner, $attendee, $nonParticipant] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs($owner)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('members', 3)
                ->where('members', fn ($members) => collect($members)->pluck('name')->sort()->values()->all()
                    === ['Attendee', 'Non-Participant', 'Owner'])
                ->where('members', fn ($members) => collect($members)
                    ->firstWhere('user_id', $nonParticipant->id)['total_sessions_count'] === 0)
                ->where('members', fn ($members) => collect($members)
                    ->firstWhere('user_id', $attendee->id)['sessions_attended_count'] === 5)
            );
    }

    public function test_members_carry_the_participation_counts_and_yet_to_mob_with_names()
    {
        [$owner, $attendee, $nonParticipant] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs($owner)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('members', function ($members) use ($owner, $nonParticipant) {
                    $row = collect($members)->firstWhere('user_id', $owner->id);

                    return $row['sessions_hosted_count'] === 5
                        && $row['sessions_attended_count'] === 0
                        && $row['sessions_watched_count'] === 0
                        && $row['total_sessions_count'] === 5
                        && $row['has_not_mobbed_with_count'] === 1
                        && collect($row['has_not_mobbed_with'])->pluck('name')->all() === [$nonParticipant->name];
                })
            );
    }

    public function test_members_carry_their_avatar_so_the_table_can_show_it()
    {
        [$owner] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs($owner)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('members', fn ($members) => collect($members)
                    ->firstWhere('user_id', $owner->id)['avatar'] === $owner->avatar)
            );
    }

    public function test_members_omits_users_who_are_invisible_in_statistics()
    {
        [$owner] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs($owner)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('members', fn ($members) => ! collect($members)->pluck('name')->contains('Opt-out of stats'))
            );
    }

    public function test_the_member_date_range_defaults_to_the_first_ever_session_through_today()
    {
        [$owner] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs($owner)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('start_date', today()->subDays(7)->toDateString())
                ->where('end_date', today()->toDateString())
                ->where('first_session_date', today()->subDays(7)->toDateString())
            );
    }

    public function test_the_member_date_range_falls_back_to_today_when_there_are_no_sessions()
    {
        $this->setTestNowToASafeWednesday();

        $this->actingAs(User::factory()->vehiklMember()->create())
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('start_date', today()->toDateString())
                ->where('end_date', today()->toDateString())
                ->where('first_session_date', today()->toDateString())
            );
    }

    public function test_member_counts_are_narrowed_by_the_requested_date_range()
    {
        [$owner, $attendee] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs($owner)
            ->get(route('statistics.index', [
                'start_date' => today()->subDays(2)->toDateString(),
                'end_date' => today()->toDateString(),
            ]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('start_date', today()->subDays(2)->toDateString())
                ->where('end_date', today()->toDateString())
                ->where('members', fn ($members) => collect($members)
                    ->firstWhere('user_id', $attendee->id)['sessions_attended_count'] === 2)
            );
    }

    public function test_mobbed_with_pairings_are_limited_to_the_requested_date_range()
    {
        $this->setTestNowToASafeWednesday();

        [$owner, $attendee] = User::factory()->vehiklMember()->count(2)
            ->sequence(['name' => 'Owner'], ['name' => 'Attendee'])
            ->create(['is_visible_in_statistics' => true]);

        $this->makeGrowthSessionWithSingleAttendee($attendee, $owner, today()->subDays(30));

        $this->actingAs($owner)
            ->get(route('statistics.index', [
                'start_date' => today()->subDays(7)->toDateString(),
                'end_date' => today()->toDateString(),
            ]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('members', fn ($members) => collect(collect($members)
                    ->firstWhere('user_id', $owner->id)['has_not_mobbed_with'])
                    ->pluck('name')->all() === ['Attendee'])
            );
    }

    public function test_a_malformed_date_falls_back_to_the_default_range_rather_than_failing()
    {
        [$owner] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs($owner)
            ->get(route('statistics.index', ['start_date' => 'the-day-before-yesterday']))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('start_date', today()->subDays(7)->toDateString())
                ->where('end_date', today()->toDateString())
            );
    }

    public function test_a_date_that_is_not_a_real_calendar_day_falls_back_to_the_default_range()
    {
        [$owner] = $this->setupFiveDaysWorthOfGrowthSessions();

        // `strtotime` would normalise this to March 1st and quietly use a range nobody asked for.
        $this->actingAs($owner)
            ->get(route('statistics.index', ['start_date' => '2020-02-30']))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('start_date', today()->subDays(7)->toDateString())
                ->where('end_date', today()->toDateString())
            );
    }

    public function test_a_range_outside_the_projects_history_is_clamped_to_it()
    {
        [$owner] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs($owner)
            ->get(route('statistics.index', [
                'start_date' => '1999-01-01',
                'end_date' => today()->addYears(5)->toDateString(),
            ]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('start_date', today()->subDays(7)->toDateString())
                ->where('end_date', today()->toDateString())
            );
    }

    public function test_members_omit_the_unused_half_of_the_mobbed_with_matrix()
    {
        [$owner] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs($owner)
            ->get(route('statistics.index'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('members', fn ($members) => collect($members)
                    ->every(fn ($member) => ! array_key_exists('has_mobbed_with', $member)))
            );
    }

    private function makeGrowthSessionOwnedBy(User $owner, CarbonInterface $date): void
    {
        GrowthSession::factory()
            ->hasAttached($owner, ['user_type_id' => Role::Owner->value], 'owners')
            ->create(['date' => $date]);
    }

    private function makeGrowthSessionWithSingleAttendee(
        User $attendee,
        User $owner,
        CarbonInterface $date
    ): void {
        GrowthSession::factory()
            ->hasAttached($attendee, ['user_type_id' => Role::Attendee->value], 'attendees')
            ->hasAttached($owner, ['user_type_id' => Role::Owner->value], 'owners')
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
