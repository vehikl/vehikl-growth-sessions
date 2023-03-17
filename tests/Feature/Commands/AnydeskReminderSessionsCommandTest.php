<?php

namespace Tests\Feature\Commands;

use App\GrowthSession;
use Carbon\Carbon;
use Tests\TestCase;

class AnydeskReminderSessionsCommandTest extends TestCase
{
    public function test_it_creates_a_reminder_growth_session_on_the_upcoming_friday()
    {
        $this->setTestNow('last Tuesday');
        $this->assertEquals(0, GrowthSession::query()->count());

        $this->artisan('create:anydesk-reminder')->assertOk();

        $this->assertEquals(1, GrowthSession::query()->count());

        $growthSession = GrowthSession::query()->first();

        $expectedDate = Carbon::parse('next Friday');
        $this->assertEquals($expectedDate, $growthSession->date);
    }

    public function test_it_does_not_duplicate_reminders()
    {
        $this->artisan('create:anydesk-reminder')->assertOk();
        $this->artisan('create:anydesk-reminder')->assertOk();

        $this->assertEquals(1, GrowthSession::query()->count());
    }
}
