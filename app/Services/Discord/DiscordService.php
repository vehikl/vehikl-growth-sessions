<?php

namespace App\Services\Discord;

use App\GrowthSession;
use App\Services\Discord\Models\Channel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class DiscordService
{
    public function getChannels(): Collection
    {
        if (!config('services.discord.bot_token') || !config('services.discord.guild_id')) {
            return new Collection();
        }

        $channels = Http::withHeaders(['Authorization' => 'Bot ' . config('services.discord.bot_token')])->get(
            'https://discord.com/api/guilds/' . config('services.discord.guild_id') . '/channels'
        )->json();

        return collect($channels)
            ->filter(function (array $channel) {
                return $channel['parent_id'] !== config('services.discord.vidya_id')
                    && $channel['id'] !== config('services.discord.vidya_id');
            })
            ->map(fn(array $channel) => new Channel($channel['id'], $channel['name']));
    }

    public function getOccupiedChannels(string $toDateString): Collection
    {
        return GrowthSession::query()
            ->where('date', ">=", $toDateString)
            ->whereNotNull('discord_channel_id')
            ->get()
            ->map(fn(GrowthSession $gs) => new Channel($gs->discord_channel_id, ''));
    }
}
