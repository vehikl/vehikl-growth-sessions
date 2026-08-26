<?php

namespace Tests\Unit\Enums;

use App\Enums\Role;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleTest extends TestCase
{
    /**
     * The whole point of the enum: a role added without an answer to one of these is a runtime
     * error here rather than a query somewhere that quietly leaves it out.
     */
    public function test_every_role_answers_every_question()
    {
        foreach (Role::cases() as $role) {
            $this->assertNotEmpty($role->label());
            $this->assertNotEmpty($role->broadcastType());
            $this->assertIsBool($role->occupiesASeat());
            $this->assertIsBool($role->countsAsPresent());
        }
    }

    public function test_hosting_takes_a_seat_and_spectating_and_waiting_take_none()
    {
        $this->assertSame([Role::Owner->value, Role::Attendee->value], Role::occupyingASeat());
    }

    public function test_waiting_in_line_is_the_one_role_that_is_not_in_the_room()
    {
        $this->assertFalse(Role::Waitlisted->countsAsPresent());
        $this->assertTrue(Role::Owner->countsAsPresent());
        $this->assertTrue(Role::Attendee->countsAsPresent());
        $this->assertTrue(Role::Watcher->countsAsPresent());
    }

    /** The ids are foreign keys, so the enum has to agree with the rows the migrations seeded. */
    public function test_it_names_the_roles_the_user_types_table_stores()
    {
        $stored = DB::table('user_types')->pluck('type', 'id')->all();

        $this->assertEquals($stored, collect(Role::cases())
            ->mapWithKeys(fn (Role $role) => [$role->value => $role->label()])
            ->all());
    }
}
