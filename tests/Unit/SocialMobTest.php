<?php

namespace Tests\Unit;

use App\SocialMob;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialMobTest extends TestCase
{
    use RefreshDatabase;

    public function testItSetsEndTimeTo5PMByDefault()
    {
        $mob = new SocialMob([
            'owner_id' => factory(User::class)->create()->id,
            'start_time' => '15:30:00',
            'date' => '2020-01-01',
            'topic' => 'does not matter',
            'location' => 'not important either'
        ]);
        $mob->save();

        $this->assertEquals('5:00 pm', $mob->fresh()->end_time);
    }
}
