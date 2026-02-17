<?php

namespace App\Listeners;

use App\Events\GrowthSessionDeleted;
use App\Events\Slack\AnnounceGrowthSession;
use App\Services\Slack\SessionPoster;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Client\ConnectionException;

class SlackPostSubscriber
{
    public function __construct(protected SessionPoster $slack)
    {
    }

    /**
     * @throws CircularDependencyException
     * @throws BindingResolutionException
     * @throws ConnectionException
     */
    public function handlePostGrowthSessionOnSlack(AnnounceGrowthSession $event): void
    {
        $this->slack->post($event->growthSession);
    }

    public function handleDeleteGrowthSessionOnSlack(GrowthSessionDeleted $event): void
    {
        $this->slack->delete($event->growthSession);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            AnnounceGrowthSession::class => 'handlePostGrowthSessionOnSlack',
            GrowthSessionDeleted::class => 'handleDeleteGrowthSessionOnSlack',
        ];
    }

}
