<?php

namespace App\Listeners;

use App\Events\GrowthSessionUpdated;
use Illuminate\Support\Facades\Http;

class NotifyGrowthSessionUpdate extends WebHookNotificationEventListener
{
    public function handle(GrowthSessionUpdated $event)
    {
        $wasGrowthSessionOriginallyToday = today()->isSameDay($event->originalGrowthSessionAttributes['date']);
        $wasMobMovedToToday = today()->isSameDay($event->newGrowthSessionAttributes['date']);

        if ($this->isWithinWebHookNotificationWindow()
            && config('webhooks.updated_today')
            && ($wasGrowthSessionOriginallyToday || $wasMobMovedToToday)) {

            Http::post(config('webhooks.updated_today'), $event->newGrowthSessionAttributes);
        }
    }
}
