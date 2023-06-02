<?php

namespace Database\Seeders;

use App\GrowthSession;
use App\User;
use App\UserType;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BusyMonthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dayOfWeek = today()->dayOfWeek;
        $monday = match (TRUE) {
            $dayOfWeek === CarbonInterface::MONDAY => CarbonImmutable::today(),
            $dayOfWeek < CarbonInterface::MONDAY => CarbonImmutable::parse('Next Monday'),
            $dayOfWeek > CarbonInterface::MONDAY => CarbonImmutable::parse('Last Monday'),
        };
        // Monday - 8 Private + 1 Public
        $privateMondaySessions = GrowthSession::factory(8)->create([
            'date' => $monday,
            'is_public' => FALSE,
            'start_time' => Carbon::createFromTime(random_int(15, 16)),
            'attendee_limit' => random_int(4, 5),
            'allow_watchers' => TRUE
        ]);

        foreach($privateMondaySessions as &$privateMondaySession) {
            $owner = User::factory()->vehiklMember()->create();
            $owner->growthSessions()->attach($privateMondaySession, ['user_type_id' => UserType::OWNER_ID]);
            $users = User::inRandomOrder()->limit(3)->get();
            foreach($users as &$user) {
                $privateMondaySession->attendees()->attach($user);
            }
        }

        $publicMondaySessions = GrowthSession::factory(1)->create([
            'date' => $monday,
            'is_public' => TRUE,
            'start_time' => Carbon::createFromTime(random_int(15, 16)),
            'attendee_limit' => random_int(4, 5),
            'allow_watchers' => TRUE
        ]);

        foreach($publicMondaySessions as $publicMondaySession) {
            $owner = User::factory()->vehiklMember()->create();
            $owner->growthSessions()->attach($publicMondaySession, ['user_type_id' => UserType::OWNER_ID]);
            $users = User::inRandomOrder()->limit(3)->get();
            foreach($users as &$user) {
                $publicMondaySession->attendees()->attach($user);
            }
        }


        // Tuesday - 10 Private + 2 Public
        $privateTuesdaySessions = GrowthSession::factory(10)->create([
            'date' => $monday->addDay(),
            'is_public' => FALSE,
            'start_time' => Carbon::createFromTime(random_int(15, 16))
        ]);

        foreach($privateTuesdaySessions as $privateTuesdaySession) {
            $owner = User::factory()->vehiklMember()->create();
            $owner->growthSessions()->attach($privateTuesdaySession, ['user_type_id' => UserType::OWNER_ID]);
            $users = User::inRandomOrder()->limit(3)->get();
            foreach($users as &$user) {
                $privateTuesdaySession->attendees()->attach($user);
            }
        }

        $publicTuesdaySessions = GrowthSession::factory(2)->create([
            'date' => $monday->addDay(),
            'is_public' => TRUE,
            'start_time' => Carbon::createFromTime(random_int(15, 16)),
            'attendee_limit' => random_int(4, 5),
            'allow_watchers' => TRUE,
        ]);

        foreach($publicTuesdaySessions as $publicTuesdaySession) {
            $owner = User::factory()->vehiklMember()->create();
            $owner->growthSessions()->attach($publicTuesdaySession, ['user_type_id' => UserType::OWNER_ID]);
            $users = User::inRandomOrder()->limit(3)->get();
            foreach($users as &$user) {
                $publicTuesdaySession->attendees()->attach($user);
            }
        }

        // Wednesday - 10 Private + 1 Public
        $privateWednesdaySessions = GrowthSession::factory(10)->create([
            'date' => $monday->addDays(2),
            'is_public' => FALSE,
            'start_time' => Carbon::createFromTime(random_int(15, 16))
        ]);

        foreach($privateWednesdaySessions as $privateWednesdaySession) {
            $owner = User::factory()->vehiklMember()->create();
            $owner->growthSessions()->attach($privateWednesdaySession, ['user_type_id' => UserType::OWNER_ID]);
            $users = User::inRandomOrder()->limit(5)->get();
            foreach($users as &$user) {
                $privateWednesdaySession->attendees()->attach($user);
            }
        }

        $publicWednesdaySession = GrowthSession::factory()->create([
            'date' => $monday->addDays(2),
            'is_public' => TRUE,
            'start_time' => Carbon::createFromTime(random_int(15, 16)),
            'attendee_limit' => 5,
            'allow_watchers' => TRUE
        ]);

        $owner = User::factory()->vehiklMember()->create();
        $owner->growthSessions()->attach($publicWednesdaySession, ['user_type_id' => UserType::OWNER_ID]);
        $users = User::inRandomOrder()->limit(3)->get();
        foreach($users as &$user) {
            $publicWednesdaySession->attendees()->attach($user);
        }
        // Thursday - 9 Private + 3 Public
        $privateThursdaySessions = GrowthSession::factory(9)->create([
            'date' => $monday->addDays(3),
            'is_public' => FALSE,
            'start_time' => Carbon::createFromTime(random_int(15, 16))
        ]);

        foreach($privateThursdaySessions as $privateThursdaySession) {
            $owner = User::factory()->vehiklMember()->create();
            $owner->growthSessions()->attach($privateThursdaySession, ['user_type_id' => UserType::OWNER_ID]);
            $users = User::inRandomOrder()->limit(5)->get();
            foreach($users as &$user) {
                $privateThursdaySession->attendees()->attach($user);
            }
        }

        $publicThursdaySessions = GrowthSession::factory(3)->create([
            'date' => $monday->addDays(3),
            'is_public' => TRUE,
            'start_time' => Carbon::createFromTime(random_int(15, 16)),
            'attendee_limit' => random_int(4, 5),
            'allow_watchers' => TRUE
        ]);

        foreach($publicThursdaySessions as $publicThursdaySession) {
            $owner = User::factory()->vehiklMember()->create();
            $owner->growthSessions()->attach($publicThursdaySession, ['user_type_id' => UserType::OWNER_ID]);
            $users = User::inRandomOrder()->limit(3)->get();
            foreach($users as &$user) {
                $publicThursdaySession->attendees()->attach($user);
            }
        }
        // Friday - 7 Private + 2 Public
        $privateFridaySessions = GrowthSession::factory(7)->create([
            'date' => $monday->addDays(4),
            'is_public' => FALSE,
            'start_time' => Carbon::createFromTime(random_int(15, 16))
        ]);

        foreach($privateFridaySessions as $privateFridaySession) {
            $owner = User::factory()->vehiklMember()->create();
            $owner->growthSessions()->attach($privateFridaySession, ['user_type_id' => UserType::OWNER_ID]);
            $users = User::inRandomOrder()->limit(3)->get();
            foreach($users as &$user) {
                $privateFridaySession->attendees()->attach($user);
            }
        }

        $publicFridaySessions = GrowthSession::factory(2)->create([
            'date' => $monday->addDays(4),
            'is_public' => TRUE,
            'start_time' => Carbon::createFromTime(random_int(15, 16)),
            'attendee_limit' => random_int(4, 5),
            'allow_watchers' => TRUE
        ]);

        foreach($publicFridaySessions as $publicFridaySession) {
            $owner = User::factory()->vehiklMember()->create();
            $owner->growthSessions()->attach($publicFridaySession, ['user_type_id' => UserType::OWNER_ID]);
            $users = User::inRandomOrder()->limit(3)->get();
            foreach($users as &$user) {
                $publicFridaySession->attendees()->attach($user);
            }
        }
    }
}
