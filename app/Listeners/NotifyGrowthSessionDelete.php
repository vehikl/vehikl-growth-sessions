<?php

namespace App\Listeners;

use App\Events\GrowthSessionDeleted;
use Illuminate\Support\Facades\Http;

class NotifyGrowthSessionDelete extends WebHookNotificationEventListener
{
    public function handle(GrowthSessionDeleted $event)
    {
        if ($this->isWithinWebHookNotificationWindow()
            && config('webhooks.deleted_today')
            && today()->isSameDay($event->growthSession->date)) {

            Http::post(config('webhooks.deleted_today'), $event->growthSession->toArray());
        }
    }
}
