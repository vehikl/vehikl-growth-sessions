<?php

namespace App\Listeners;

use App\Events\GrowthSessionCreated;
use Illuminate\Support\Facades\Http;

class NotifyGrowthSessionCreation extends WebHookNotificationEventListener
{
    public function handle(GrowthSessionCreated $event)
    {
        if ($this->isWithinWebHookNotificationWindow()
            && config('webhooks.created_today')
            && today()->isSameDay($event->growthSession->date)) {

            Http::post(config('webhooks.created_today'), $event->growthSession->toArray());
        }
    }
}
