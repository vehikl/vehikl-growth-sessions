<?php

namespace Tests\Feature\Commands;

use App\Models\GrowthSession;
use App\Models\User;
use App\Notifications\GrowthSessionDeletedNotification;
use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class PruneNotificationsCommandTest extends TestCase
{
    private const RETENTION_DAYS = 7;
    private User $user;
    private GrowthSession $growthSession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setTestNowToASafeWednesday();

        $this->user = User::factory()->create();
        $this->growthSession = GrowthSession::factory()->create();
    }

    public function test_it_deletes_notifications_older_than_the_retention_window()
    {
        $this->notifyAt(now()->subDays(self::RETENTION_DAYS)->subMinute());

        $this->artisan('notifications:prune')->assertSuccessful();

        $this->assertSame(0, DatabaseNotification::query()->count());
    }

    public function test_it_keeps_notifications_within_the_retention_window()
    {
        $this->notifyAt(now()->subDays(self::RETENTION_DAYS)->addMinute());

        $this->artisan('notifications:prune')->assertSuccessful();

        $this->assertSame(1, DatabaseNotification::query()->count());
    }

    public function test_it_keeps_read_and_unread_notifications_alike()
    {
        $this->notifyAt(now());
        $this->notifyAt(now());
        $this->user->notifications()->first()->markAsRead();

        $this->artisan('notifications:prune')->assertSuccessful();

        $this->assertSame(2, DatabaseNotification::query()->count());
    }

    public function test_the_retention_window_is_configurable()
    {
        $this->notifyAt(now()->subDays(2));

        $this->artisan('notifications:prune', ['--days' => 1])->assertSuccessful();

        $this->assertSame(0, DatabaseNotification::query()->count());
    }

    public function test_it_refuses_to_run_with_an_invalid_retention_window()
    {
        $this->notifyAt(now());

        $this->artisan('notifications:prune', ['--days' => -7])->assertFailed();
        $this->artisan('notifications:prune', ['--days' => 'abc'])->assertFailed();

        $this->assertSame(1, DatabaseNotification::query()->count());
    }

    private function notifyAt(CarbonInterface $sentAt): void
    {
        $now = now();

        $this->setTestNow($sentAt);
        $this->user->notify(new GrowthSessionDeletedNotification($this->growthSession));
        $this->setTestNow($now);
    }

}
