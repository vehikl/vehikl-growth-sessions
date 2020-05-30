<?php

namespace Tests\Unit;

use App\SocialMob;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialMobTest extends TestCase
{
    use RefreshDatabase;

    public function testItCanHaveTheDateStartModifiedWhileKeepingTheStartHourUnchanged()
    {
        $mob = factory(SocialMob::class)->create(['start_time' => '2020-01-01T20:00:00']);
        $mob->date = '2015-02-03';
        $mob->save();

        $this->assertEquals('2015-02-03 20:00:00', $mob->fresh()->start_time);
    }

    public function testItSetsEndTimeTo5PMByDefault()
    {
        $mob = new SocialMob([
            'owner_id' => factory(User::class)->create()->id,
            'start_time' => '2020-01-01T15:30:00',
            'topic' => 'does not matter',
            'location' => 'not important either'
        ]);
        $mob->save();

        $this->assertEquals('2020-01-01 17:00:00', $mob->fresh()->end_time);
    }

    public function testItAcceptsOtherDateTimeValuesForEndTime()
    {
        $mob = new SocialMob([
            'owner_id' => factory(User::class)->create()->id,
            'start_time' => '2020-01-01T15:30:00',
            'end_time' => '2020-01-01T16:00:00',
            'topic' => 'does not matter',
            'location' => 'not important either'
        ]);
        $mob->save();

        $this->assertEquals('2020-01-01 16:00:00', $mob->fresh()->end_time);
    }

    public function testWhenAMobDateIsChangedBothTheStartAndAndTimeAreUpdated()
    {
        $mob = factory(SocialMob::class)->create(
            [
                'start_time' => '2020-01-01T15:00:00',
                'end_time' => '2020-01-01T16:00:00',
            ]);
        $mob->date = '2015-02-03';
        $mob->save();

        $this->assertEquals('2015-02-03 15:00:00', $mob->fresh()->start_time);
        $this->assertEquals('2015-02-03 16:00:00', $mob->fresh()->end_time);
    }
}
