<?php

namespace Tests\Http\Controllers;

use App\Models\GrowthSession;
use App\Http\Controllers\GrowthSessionController;
use App\Models\Tag;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class GrowthSessionsWeekTest extends TestCase
{

    public function testWeek()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');


        $tag = Tag::factory()->create();
        GrowthSession::factory()
            ->hasAttached(
                $tag,
                [],
                'tags'
            )
            ->create(['date' => $monday, 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();

        $response = $this->getJson(route('growth_sessions.week', [
            'date' => $monday
        ]))
            ->assertSuccessful();
        $this->assertEquals($tag->name, $response[$monday->toDateString()][0]['tags'][0]['name']);
    }
}
