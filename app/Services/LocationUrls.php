<?php

namespace App\Services;

use App\Models\GrowthSession;
use Illuminate\Support\Uri;
use League\Uri\Exceptions\SyntaxError;

class LocationUrls
{
    public function get(GrowthSession $growthSession): array
    {
        $lines = explode(PHP_EOL, $growthSession->location);
        $urls = collect($lines)
            ->filter(function ($line) {
                try {
                    $uri = new Uri($line);
                    return !!$uri->host();
                } catch (SyntaxError $e) {
                    return false;
                }
            })
            ->values()
            ->toArray();

        if (isset($growthSession->discord_channel_id)) {
            $urls []= sprintf("discord://discordapp.com/%s/%s", config('services.discord.guild_id'), $growthSession->discord_channel_id);
        }

        return $urls;
    }
}
