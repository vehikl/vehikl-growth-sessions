<?php

namespace App\Listeners;

use App\Events\GrowthSessionAttendeeChanged;
use App\Events\GrowthSessionCreated;
use App\Events\GrowthSessionDeleted;
use App\Events\GrowthSessionUpdated;
use App\Models\GrowthSession;
use App\Services\Slack\SessionPoster;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;

class GrowthSessionSlackSubscriber
{
    public function __construct(protected SessionPoster $slack)
    {
    }

    public function cooldownLock(GrowthSession $gs): bool
    {
        $key = "slack:cooldown:{$gs->id}";
        if (Cache::has($key)) {
            return false;
        }
        Cache::put($key, true, now()->addSeconds(2));
        return true;
    }

    /**
     * @throws CircularDependencyException
     * @throws BindingResolutionException
     * @throws ConnectionException
     */
    public function handleCreated(GrowthSessionCreated $event): void
    {
        if (!$this->cooldownLock($event->growthSession)) {
            return;
        }
        $this->slack->post($event->growthSession);
    }

    /**
     * @throws CircularDependencyException
     * @throws BindingResolutionException
     * @throws ConnectionException
     */
    public function handleAttendeeChanged(GrowthSessionAttendeeChanged $event): void
    {
        if (!$this->cooldownLock($event->growthSession)) {
            return;
        }

        $this->slack->post($event->growthSession);
    }

    /**
     * @throws CircularDependencyException
     * @throws BindingResolutionException
     * @throws ConnectionException
     */
    public function handleUpdated(GrowthSessionUpdated $event): void
    {
        if (!$this->cooldownLock($event->growthSession)) {
            return;
        }
        if (in_array('slack_thread_ts', $event->dirtyKeys)) {
            return;
        }

        $this->slack->post($event->growthSession);
    }

    public function handleDeleted(GrowthSessionDeleted $event): void
    {
        if (!$this->cooldownLock($event->growthSession)) {
            return;
        }

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
