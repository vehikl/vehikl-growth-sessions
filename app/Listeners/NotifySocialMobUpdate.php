<?php

namespace App\Listeners;

use App\Events\GrowthSessionUpdated;
use Illuminate\Support\Facades\Http;

class NotifySocialMobUpdate extends WebHookNotificationEventListener
{
    public function handle(GrowthSessionUpdated $event)
    {
        $wasMobOriginallyToday = today()->isSameDay($event->originalGrowthSessionAttributes['date']);
        $wasMobMovedToToday = today()->isSameDay($event->newGrowthSessionAttributes['date']);

        if ($this->isWithinWebHookNotificationWindow()
            && config('webhooks.updated_today')
            && ($wasMobOriginallyToday || $wasMobMovedToToday)) {

            Http::post(config('webhooks.updated_today'), $event->newGrowthSessionAttributes);
        }
    }
}
