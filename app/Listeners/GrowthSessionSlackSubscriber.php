<?php

namespace App\Listeners;

use App\Events\GrowthSessionAttendeeChanged;
use App\Events\GrowthSessionCreated;
use App\Events\GrowthSessionDeleted;
use App\Events\GrowthSessionUpdated;
use App\Services\Slack\SessionPoster;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Client\ConnectionException;

class GrowthSessionSlackSubscriber
{
    public function __construct(protected SessionPoster $slack)
    {
    }

    /**
     * @throws CircularDependencyException
     * @throws BindingResolutionException
     * @throws ConnectionException
     */
    public function handleCreated(GrowthSessionCreated $event): void
    {
        $this->slack->post($event->growthSession);
    }

    /**
     * @throws CircularDependencyException
     * @throws BindingResolutionException
     * @throws ConnectionException
     */
    public function handleAttendeeChanged(GrowthSessionAttendeeChanged $event): void
    {
        $this->slack->post($event->growthSession);
    }

    /**
     * @throws CircularDependencyException
     * @throws BindingResolutionException
     * @throws ConnectionException
     */
    public function handleUpdated(GrowthSessionUpdated $event): void
    {
        $this->slack->post($event->growthSession);
    }

    public function handleDeleted(GrowthSessionDeleted $event): void
    {
        // TODO: Remove post
    }

    /**
     */
    public function subscribe(Dispatcher $dispatcher): array
    {
        if (!$this->slack->isConfigured()) {
            return [];
        }

        return [
            GrowthSessionCreated::class => 'handleCreated',
            GrowthSessionAttendeeChanged::class => 'handleAttendeeChanged',
            GrowthSessionUpdated::class => 'handleUpdated',
        ];
    }

}
