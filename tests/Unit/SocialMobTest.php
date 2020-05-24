<?php

namespace Tests\Unit;

use App\SocialMob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialMobTest extends TestCase
{
    use RefreshDatabase;

    public function testItCanHaveTheDateStartModifiedWhileKeepingTheStartHourUnchanged()
    {
        $mob = factory(SocialMob::class)->create(['start_time' => '2020-01-01T20:00:00']);
        $mob->start_date = '2015-02-03';
        $mob->save();

        $this->assertEquals('2015-02-03 20:00:00', $mob->fresh()->start_time);
    }
}
