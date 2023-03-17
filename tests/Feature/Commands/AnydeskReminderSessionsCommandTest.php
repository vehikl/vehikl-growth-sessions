<?php

namespace Tests\Feature\Commands;

use App\GrowthSession;
use Carbon\Carbon;
use Tests\TestCase;

class AnydeskReminderSessionsCommandTest extends TestCase
{
    public function test_it_creates_a_reminder_growth_session_on_friday()
    {
        $this->assertEquals(0, GrowthSession::query()->count());

        $this->artisan('create:anydesk-reminder')->assertOk();

        $this->assertEquals(1, GrowthSession::query()->count());

        $growthSession = GrowthSession::query()->first();
        $this->assertEquals(Carbon::FRIDAY, Carbon::parse($growthSession->date)->weekday());
    }
}
