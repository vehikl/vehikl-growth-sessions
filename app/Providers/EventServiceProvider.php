<?php

namespace App\Providers;

use App\Events\GrowthSessionAttendeeChanged;
use App\Events\GrowthSessionCreated;
use App\Events\GrowthSessionDeleted;
use App\Events\GrowthSessionUpdated;
use App\Listeners\NotifyGrowthSessionAttendeeChange;
use App\Listeners\NotifySocialMobCreation;
use App\Listeners\NotifySocialMobDelete;
use App\Listeners\NotifySocialMobUpdate;
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
        GrowthSessionCreated::class => [NotifySocialMobCreation::class],
        GrowthSessionAttendeeChanged::class => [NotifyGrowthSessionAttendeeChange::class],
        GrowthSessionUpdated::class => [NotifySocialMobUpdate::class],
        GrowthSessionDeleted::class => [NotifySocialMobDelete::class]
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
