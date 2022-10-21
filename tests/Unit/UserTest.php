<?php

namespace Tests\Unit;

use App\GrowthSession;
use App\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function testItDoesNotExposeUserEmails()
    {
        $user = User::factory()->create();

        $this->assertEmpty($user->toArray()['email'] ?? null);
    }

    public function testItCastsIsVehiklMemberToBool()
    {
        $user = User::factory()->create();

        $this->assertIsBool($user->is_vehikl_member);
    }

    public function testItCanGetIsNewUser()
    {
        $user = User::factory()->create();
        $this->assertTrue($user->isFirstTimeUser());
    }

    public function testItWillReturnFalseForIsNewUserIfGrowthSessionExists()
    {
        $user = User::factory()->create();
        $growthSession = GrowthSession::factory()
            ->hasAttached($user, ['user_type_id' => UserType::OWNER_ID], 'owners')->create();

//        dd($user->fresh()->isFirstTimeUser());
        //TODO: get PHPUnit working
        //TODO: Figure out how to see the DB
        //Path forward: add the firstTimeUser to GrowthSession response payload
        //Have frontend go "oh yay, its in the payload, show modal"
        $this->assertFalse($user->fresh()->isFirstTimeUser());
    }


}
