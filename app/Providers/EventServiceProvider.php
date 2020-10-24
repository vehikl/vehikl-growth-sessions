<?php

namespace App\Providers;

use App\Events\SocialMobAttendeeChanged;
use App\Events\SocialMobCreated;
use App\Listeners\NotifySocialMobAttendeeChange;
use App\Listeners\NotifySocialMobCreation;
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
        SocialMobCreated::class => [NotifySocialMobCreation::class],
        SocialMobAttendeeChanged::class => [NotifySocialMobAttendeeChange::class]
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
