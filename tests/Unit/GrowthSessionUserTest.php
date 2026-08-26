<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Models\GrowthSessionUser;
use Tests\TestCase;

class GrowthSessionUserTest extends TestCase
{
    public function test_it_reads_the_role_back_as_the_role_it_was_given()
    {
        $enrolment = new GrowthSessionUser;

        $enrolment->user_type_id = Role::Waitlisted;

        $this->assertSame(Role::Waitlisted, $enrolment->user_type_id);
        $this->assertSame(Role::Waitlisted->value, $enrolment->getAttributes()['user_type_id']);
    }

    public function test_it_takes_the_role_as_the_stored_id_too()
    {
        $enrolment = new GrowthSessionUser;

        $enrolment->user_type_id = Role::Watcher->value;

        $this->assertSame(Role::Watcher, $enrolment->user_type_id);
    }

    /**
     * A role the database knows and this deploy does not must not cost the caller the whole row -
     * whoever reads it decides what to do about not knowing, as the pivot observer does when it
     * falls back to announcing an attendee change.
     */
    public function test_a_role_this_application_has_no_case_for_reads_as_nothing_rather_than_throwing()
    {
        $enrolment = (new GrowthSessionUser)->setRawAttributes(['user_type_id' => 99]);

        $this->assertNull($enrolment->user_type_id);
    }

    public function test_a_row_with_no_role_at_all_reads_as_nothing()
    {
        $enrolment = (new GrowthSessionUser)->setRawAttributes(['user_type_id' => null]);

        $this->assertNull($enrolment->user_type_id);
    }
}
