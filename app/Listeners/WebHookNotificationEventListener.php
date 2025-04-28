<?php


namespace App\Listeners;


use Carbon\Carbon;

abstract class WebHookNotificationEventListener
{
    protected function isWithinWebHookNotificationWindow(): bool
    {
        return now()
            ->isBetween(Carbon::parse(config('webhooks.start_time')), Carbon::parse(config('webhooks.end_time')));
    }
}
