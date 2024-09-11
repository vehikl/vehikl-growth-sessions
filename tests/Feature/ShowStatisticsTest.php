<?php

namespace Tests\Feature;

use App\GrowthSession;
use App\User;
use App\UserType;
use Carbon\CarbonInterface;
use Tests\TestCase;

class ShowStatisticsTest extends TestCase
{
    public function testItIsAccessibleByVehikaliens()
    {
        $this->actingAs(User::factory()->vehiklMember()->create())
            ->getJson(route('statistics'))
            ->assertSuccessful();
    }

    public function testGuestsCannotAccessStatistics()
    {
        $this->getJson(route('statistics'))
            ->assertUnauthorized();
    }

    public function testNonVehikaliensCannotAccessStatistics()
    {
        $this->actingAs(User::factory()->vehiklMember(false)->create())
            ->getJson(route('statistics'))
            ->assertForbidden();
    }

    public function testItReturnsParticipationCountStatisticsForAllUsersInTheSystem()
    {
        [$owner, $attendee, $nonParticipant] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs(User::factory()->vehiklMember()->create())
            ->getJson(route('statistics'))
            ->assertSuccessful()
            ->assertJson([
                'start_date' => today()->subDays(7)->toDateString(),
                'end_date' => today()->toDateString(),
                'users' => [
                    [
                        'name' => $owner->name,
                        'user_id' => $owner->id,
                        'total_participation' => 5,
                        'hosted' => 5,
                        'attended' => 0,
                        'watched' => 0,

                    ],
                    [
                        'name' => $attendee->name,
                        'user_id' => $attendee->id,
                        'total_participation' => 5,
                        'hosted' => 0,
                        'attended' => 5,
                        'watched' => 0,
                    ],
                    [
                        'name' => $nonParticipant->name,
                        'user_id' => $nonParticipant->id,
                        'total_participation' => 0,
                        'hosted' => 0,
                        'attended' => 0,
                        'watched' => 0,
                    ],
                ]
            ]);
    }

    public function testItAlsoIncludesAListOfPeopleTheyHaveMobbedWith()
    {
        [$owner, $attendee, $nonParticipant] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs($owner)
            ->getJson(route('statistics'))
            ->assertSuccessful()
            ->assertJson([
                'users' => [
                    [
                        'name' => $owner->name,
                        'user_id' => $owner->id,
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
                        'has_mobbed_with' => [],
                    ],
                ]
            ]);
    }

    public function testItAlsoIncludesAListOfPeopleTheyHaveNotMobbedWith()
    {
        [$owner, $attendee, $nonParticipant] = $this->setupFiveDaysWorthOfGrowthSessions();

        $this->actingAs($owner)
            ->getJson(route('statistics'))
            ->assertSuccessful()
            ->assertJson([
                'users' => [
                    [
                        'name' => $owner->name,
                        'user_id' => $owner->id,
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

    private function makeGrowthSessionWithSingleAttendee(
        User $attendee,
        User $owner,
        CarbonInterface $date
    ): GrowthSession {
        return GrowthSession::factory()
            ->hasAttached($attendee, ['user_type_id' => UserType::ATTENDEE_ID], 'attendees')
            ->hasAttached($owner, ['user_type_id' => UserType::OWNER_ID], 'watchers')
            ->create(['date' => $date]);
    }

    private function setupFiveDaysWorthOfGrowthSessions(): array
    {
        $this->setTestNowToASafeWednesday();

        [$owner, $attendee, $nonParticipant] = User::factory()->vehiklMember()->count(3)
            ->sequence(
                ['name' => 'Owner'],
                ['name' => 'Attendee'],
                ['name' => 'Non-Participant'],
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
