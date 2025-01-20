<?php

namespace Tests\Feature\Commands;

use App\GrowthSession;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RecalculateStatisticsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        date_default_timezone_set('America/Toronto');
    }

    public function test_it_clears_statistics_cache()
    {
        $this->setTestNow('2020-01-15');
        User::factory()->create();
        $attendess = User::factory()->times(4)->create();
        $oldestGrowthSession = GrowthSession::factory()->create(['attendee_limit' => 4, 'date' => today()->subDay()]);
        $oldestGrowthSession->attendees()->attach($attendess);

        $startDate = $oldestGrowthSession->date->toDateString();
        $endDate = today()->toDateString();
        $expectedKey = "statistics-{$startDate}-{$endDate}";

        $this->artisan('statistics:recalculate')->assertSuccessful();

        $this->assertNotEmpty(Cache::get($expectedKey));
    }
}
