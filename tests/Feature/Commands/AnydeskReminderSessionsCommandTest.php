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

        $expectedDate = Carbon::parse('next Friday');
        $this->assertEquals($expectedDate, GrowthSession::query()->first()->date);
    }

    public function test_it_does_not_duplicate_reminders()
    {
        $this->artisan('create:anydesk-reminder')->assertOk();
        $this->artisan('create:anydesk-reminder')->assertOk();

        $this->assertEquals(1, GrowthSession::query()->count());
    }

    public function test_it_creates_the_reminder_on_the_day_if_today_is_friday()
    {
        $this->setTestNow('next Friday');
        $this->assertEquals(0, GrowthSession::query()->count());

        $this->artisan('create:anydesk-reminder')->assertOk();

        $expectedDate = today();
        $this->assertEquals($expectedDate, GrowthSession::query()->first()->date);
    }
}
