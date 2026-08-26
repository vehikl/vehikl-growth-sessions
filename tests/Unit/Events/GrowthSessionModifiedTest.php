<?php

namespace Tests\Unit\Events;

use App\Enums\Role;
use App\Events\GrowthSessionModified;
use Tests\TestCase;

class GrowthSessionModifiedTest extends TestCase
{
    /**
     * Which part of the roster a role belongs to is the event's own business, so a role added
     * without a section to redraw is a runtime error here rather than a board that quietly stops
     * refreshing one of its lists.
     */
    public function test_every_role_names_the_part_of_the_roster_it_redraws()
    {
        foreach (Role::cases() as $role) {
            $this->assertNotEmpty(GrowthSessionModified::typeFor($role));
        }
    }

    public function test_hosting_and_attending_redraw_the_same_list()
    {
        $this->assertSame(GrowthSessionModified::TYPE_ATTENDEES, GrowthSessionModified::typeFor(Role::Owner));
        $this->assertSame(GrowthSessionModified::TYPE_ATTENDEES, GrowthSessionModified::typeFor(Role::Attendee));
        $this->assertSame(GrowthSessionModified::TYPE_WATCHERS, GrowthSessionModified::typeFor(Role::Watcher));
        $this->assertSame(GrowthSessionModified::TYPE_WAITLIST, GrowthSessionModified::typeFor(Role::Waitlisted));
    }

    /** A row that never got a role still moved the seats, so the attendee list is what redraws. */
    public function test_a_row_holding_no_role_redraws_the_attendees()
    {
        $this->assertSame(GrowthSessionModified::TYPE_ATTENDEES, GrowthSessionModified::typeFor(null));
    }
}
