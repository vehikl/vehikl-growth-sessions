<?php

namespace Tests\Unit;

use App\SocialMob;
use App\User;
use Carbon\Carbon;
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

        $this->assertEquals('05:00 pm', $mob->fresh()->toArray()['end_time']);
    }

    public function testItCanHaveACustomTitle()
    {
        $titleGiven = 'My title';
        $mob = factory(SocialMob::class)->create(['title' => $titleGiven]);

        $this->assertEquals($titleGiven, $mob->title);
    }

    public function testItWillHaveADefaultTitleIfNoneIsGiven()
    {
        $mob = factory(SocialMob::class)->create(['title' => null, 'date' => Carbon::parse('Friday')]);

        $expectedTitle = "{$mob->owner->name}'s Friday Mob";

        $this->assertEquals($expectedTitle, $mob->title);
    }
}
