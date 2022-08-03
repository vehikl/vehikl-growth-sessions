<?php

namespace Tests\Unit;

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
}
