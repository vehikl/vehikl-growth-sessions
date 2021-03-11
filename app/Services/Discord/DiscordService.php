<?php

namespace App\Services\Discord;

use App\Services\Discord\Models\Channel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class DiscordService
{
    public function getChannels(): Collection
    {
        $channels = Http::withHeaders(['Authorization' => 'Bot ' . config('services.discord.bot_token')])->get(
            'https://discord.com/api/guilds/' . config('services.discord.guild_id') . '/channels'
        )->json();

        return collect($channels)
            ->filter(fn(array $channel) => is_null($channel['parent_id']))
            ->map(fn(array $channel) => new Channel($channel['id'], $channel['name']));
    }
}
