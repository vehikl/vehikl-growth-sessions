<?php

namespace Tests\Feature;

use App\GrowthSession;
use App\GrowthSessionUser;
use App\User;
use App\UserType;
use Carbon\CarbonInterface;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ShowStatisticsTest extends TestCase
{
    public function testItIsAccessibleByVehikaliens()
    {
        $this->actingAs(User::factory()->vehiklMember()->create())
            ->getJson(route('statistics.index'))
            ->assertSuccessful();
    }

    public function testGuestsCannotAccessStatistics()
    {
        $this->getJson(route('statistics.index'))
            ->assertUnauthorized();
    }

    public function testNonVehikaliensCannotAccessStatistics()
    {
        $this->actingAs(User::factory()->vehiklMember(false)->create())
            ->getJson(route('statistics.index'))
            ->assertForbidden();
    }

    public function testItIncludesAListOfPeopleTheyHaveMobbedWithAsAttendees()
    {
        [$owner, $attendee, $nonParticipant] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs($owner)
            ->getJson(route('statistics.index'))
            ->assertSuccessful()
            ->assertJson([
                'users' => [
                    [
                        'name' => $owner->name,
                        'user_id' => $owner->id,
                        'has_mobbed_with_count' => 1,
                        'has_mobbed_with' => [
                            [
                                'name' => $attendee->name,
                                'user_id' => $attendee->id,
                            ]
                        ],
                    ],
                    [
                        'name' => $attendee->name,
                        'user_id' => $attendee->id,
                        'has_mobbed_with_count' => 1,
                        'has_mobbed_with' => [
                            [
                                'name' => $owner->name,
                                'user_id' => $owner->id,
                            ]
                        ],
                    ],
                    [
                        'name' => $nonParticipant->name,
                        'user_id' => $nonParticipant->id,
                        'has_mobbed_with_count' => 0,
                        'has_mobbed_with' => [],
                    ],
                ]
            ]);
    }

    public function testItAlsoIncludesAListOfPeopleTheyHaveNotMobbedWithAsAttendees()
    {
        [$owner, $attendee, $nonParticipant] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs($owner)
            ->getJson(route('statistics.index'))
            ->assertSuccessful()
            ->assertJson([
                'users' => [
                    [
                        'name' => $owner->name,
                        'user_id' => $owner->id,
                        'has_not_mobbed_with_count' => 1,
                        'has_not_mobbed_with' => [
                            [
                                'name' => $nonParticipant->name,
                                'user_id' => $nonParticipant->id,
                            ]
                        ],
                    ],
                    [
                        'name' => $attendee->name,
                        'user_id' => $attendee->id,
                        'has_not_mobbed_with_count' => 1,
                        'has_not_mobbed_with' => [
                            [
                                'name' => $nonParticipant->name,
                                'user_id' => $nonParticipant->id,
                            ]
                        ],
                    ],
                    [
                        'name' => $nonParticipant->name,
                        'user_id' => $nonParticipant->id,
                        'has_not_mobbed_with_count' => 2,
                        'has_not_mobbed_with' => [
                            [
                                'name' => $owner->name,
                                'user_id' => $owner->id,
                            ],
                            [
                                'name' => $attendee->name,
                                'user_id' => $attendee->id,
                            ]
                        ],
                    ],
                ]
            ]);
    }

    public function testItDisregardsGrowthSessionsWithMoreThan10AttendeesWhenConsideringTheHasMobbedWith()
    {
        $owner = User::factory()->vehiklMember()->create(['is_visible_in_statistics' => true]);
        $attendees = User::factory()->vehiklMember()->count(config('statistics.max_mob_size'))->create(['is_visible_in_statistics' => true]);

        $growthSession = GrowthSession::factory()
            ->hasAttached($owner, ['user_type_id' => UserType::ATTENDEE_ID], 'owners')
            ->hasAttached($owner, ['user_type_id' => UserType::OWNER_ID], 'attendees')
            ->create();

        GrowthSessionUser::query()->insert($attendees->map(fn($attendee) => [
            'growth_session_id' => $growthSession->id,
            'user_id' => $attendee->id,
            'user_type_id' => UserType::ATTENDEE_ID,
        ])->toArray());

        $this->actingAs($owner)
            ->getJson(route('statistics.index'))
            ->assertSuccessful()
            ->assertJson([
                'users' => [
                    [
                        'name' => $owner->name,
                        'user_id' => $owner->id,
                        'has_not_mobbed_with_count' => $attendees->count()
                    ]
                ]
            ]);
    }

    public function testItAllowsSomeDevelopersToHaveParticipationValidatedEvenOnLargerGrowthSessions()
    {
        $owner = User::factory()->vehiklMember()->create(['is_visible_in_statistics' => true]);
        $loosenRulesUser = User::factory()->create(['name' => 'Exception', 'is_visible_in_statistics' => true]);
        $otherAttendees = User::factory()->vehiklMember()->count(config('statistics.max_mob_size'))->create(['is_visible_in_statistics' => true]);

        $growthSession = GrowthSession::factory()
            ->hasAttached($owner, ['user_type_id' => UserType::ATTENDEE_ID], 'owners')
            ->hasAttached($owner, ['user_type_id' => UserType::OWNER_ID], 'attendees')
            ->create();

        GrowthSessionUser::query()->insert($otherAttendees->map(fn($attendee) => [
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

        $this->actingAs($owner)
            ->getJson(route('statistics.index'))
            ->assertSuccessful()
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('users.0.has_mobbed_with.0.user_id', $loosenRulesUser->id)
                ->etc()
            );
    }

    public function testItAllowsSomeDevelopersToHaveParticipationValidatedEvenWhenTheyAttendAsWatchers()
    {
        $owner = User::factory()->vehiklMember()->create(['is_visible_in_statistics' => true]);
        $loosenRulesUser = User::factory()->create(['name' => 'Exception', 'is_visible_in_statistics' => true]);

        $growthSession = GrowthSession::factory()
            ->hasAttached($owner, ['user_type_id' => UserType::ATTENDEE_ID], 'owners')
            ->hasAttached($owner, ['user_type_id' => UserType::OWNER_ID], 'attendees')
            ->create();

        GrowthSessionUser::query()->forceCreate([
            'user_id' => $loosenRulesUser->id,
            'growth_session_id' => $growthSession->id,
            'user_type_id' => UserType::WATCHER_ID,
        ]);

        config()->set(['statistics.loosen_participation_rules.user_ids' => [$loosenRulesUser->id]]);

        $this->actingAs($owner)
            ->getJson(route('statistics.index'))
            ->assertSuccessful()
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('users.0.has_mobbed_with.0.user_id', $loosenRulesUser->id)
                ->etc()
            );
    }

    public function testTheDevelopersThatHaveLooseParticipationRulesStillNeedToBeInTheGrowthSessionToCountAsValid()
    {
        $nonParticipatingUser = User::factory()->vehiklMember()->create([
            'name' => 'Regular', 'is_visible_in_statistics' => true
        ]);
        $loosenRulesUser = User::factory()->create(['name' => 'Exception', 'is_visible_in_statistics' => true]);

        config()->set(['statistics.loosen_participation_rules.user_ids' => [$loosenRulesUser->id]]);

        $this->actingAs($nonParticipatingUser)
            ->getJson(route('statistics.index'))
            ->assertSuccessful()
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('users.0.has_mobbed_with', [])
                ->where('users.1.has_mobbed_with', [])
                ->etc()
            );
    }

    private function makeGrowthSessionWithSingleAttendee(
        User $attendee,
        User $owner,
        CarbonInterface $date
    ): GrowthSession {
        return GrowthSession::factory()
            ->hasAttached($attendee, ['user_type_id' => UserType::ATTENDEE_ID], 'attendees')
            ->hasAttached($owner, ['user_type_id' => UserType::ATTENDEE_ID], 'owners')
            ->hasAttached($owner, ['user_type_id' => UserType::OWNER_ID], 'attendees')
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
