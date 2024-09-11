<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class StatisticsTest extends TestCase
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

}
