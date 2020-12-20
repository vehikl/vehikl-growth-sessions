<?php

namespace App\Listeners;

use App\Events\SocialMobAttendeeChanged;
use Illuminate\Support\Facades\Http;

class NotifySocialMobAttendeeChange extends WebHookNotificationEventListener
{
    public function handle(SocialMobAttendeeChanged $event)
    {
        if ($this->isWithinWebHookNotificationWindow() && config('webhooks.attendees_today')) {

            Http::post(config('webhooks.attendees_today'), $event->growthSession->toArray());
        }
    }
}
