<?php

namespace App\Providers;

use App\Events\GrowthSessionAttendeeChanged;
use App\Events\GrowthSessionCreated;
use App\Events\GrowthSessionDeleted;
use App\Events\GrowthSessionUpdated;
use App\Listeners\NotifyGrowthSessionAttendeeChange;
use App\Listeners\NotifyGrowthSessionCreation;
use App\Listeners\NotifyGrowthSessionDelete;
use App\Listeners\NotifyGrowthSessionUpdate;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [SendEmailVerificationNotification::class],
        GrowthSessionCreated::class => [NotifyGrowthSessionCreation::class],
        GrowthSessionAttendeeChanged::class => [NotifyGrowthSessionAttendeeChange::class],
        GrowthSessionUpdated::class => [NotifyGrowthSessionUpdate::class],
        GrowthSessionDeleted::class => [NotifyGrowthSessionDelete::class]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
