<?php

namespace App\Listeners;

use App\Events\GrowthSessionDeleted;
use Illuminate\Support\Facades\Http;

class NotifySocialMobDelete extends WebHookNotificationEventListener
{
    public function handle(GrowthSessionDeleted $event)
    {
        if ($this->isWithinWebHookNotificationWindow()
            && config('webhooks.deleted_today')
            && today()->isSameDay($event->socialMob->date)) {

            Http::post(config('webhooks.deleted_today'), $event->socialMob->toArray());
        }
    }
}
