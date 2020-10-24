<?php

namespace App\Listeners;

use App\Events\SocialMobDeleted;
use Illuminate\Support\Facades\Http;

class NotifySocialMobDelete extends WebHookNotificationEventListener
{
    public function handle(SocialMobDeleted $event)
    {
        if ($this->isWithinWebHookNotificationWindow()
            && config('webhooks.deleted_today')
            && today()->isSameDay($event->socialMob->date)) {

            Http::post(config('webhooks.deleted_today'), $event->socialMob->toArray());
        }
    }
}
