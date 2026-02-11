<?php

namespace App\Listeners;

use App\Events\GrowthSessionModified;
use App\Services\Slack\SessionPoster;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Http\Client\ConnectionException;

class SlackPostListener
{
    public function __construct(protected SessionPoster $slack)
    {
    }

    /**
     * @throws CircularDependencyException
     * @throws BindingResolutionException
     * @throws ConnectionException
     */
    protected function postGrowthSessionOnSlack(GrowthSessionModified $event): void
    {
        $this->slack->post($event->growthSession());
    }

    protected function noop(GrowthSessionModified $event)
    {

    }

    /**
     */
    public function handle(GrowthSessionModified $event): void
    {
        if (!$this->slack->isConfigured()) {
            return;
        }

        match ($event->type) {
            GrowthSessionModified::TYPE_SESSION, GrowthSessionModified::TYPE_WATCHERS, GrowthSessionModified::TYPE_ATTENDEES => $this->postGrowthSessionOnSlack($event),
            default => $this->noop($event),
        };
    }

}
