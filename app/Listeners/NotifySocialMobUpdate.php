<?php

namespace App\Listeners;

use App\Events\SocialMobUpdated;
use Illuminate\Support\Facades\Http;

class NotifySocialMobUpdate extends WebHookNotificationEventListener
{
    public function handle(SocialMobUpdated $event)
    {
        $wasMobOriginallyToday = today()->isSameDay($event->originalSocialMobAttributes['date']);
        $wasMobMovedToToday = today()->isSameDay($event->newSocialMobAttributes['date']);

        if ($this->isWithinWebHookNotificationWindow()
            && config('webhooks.updated_today')
            && ($wasMobOriginallyToday || $wasMobMovedToToday)) {

            Http::post(config('webhooks.updated_today'), $event->newSocialMobAttributes);
        }
    }
}
