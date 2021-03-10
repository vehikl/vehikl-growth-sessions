<?php

namespace App\Services\Discord;

use App\Services\Discord\Models\Channel;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class DiscordService
{
    private Client $http;

    public function __construct(Client $http)
    {
        $this->http = $http;
    }

    public function getHttp(): Client
    {
        return $this->http;
    }

    public function getChannels(): Collection
    {
        $channels = Http::withHeaders(['Authorization' => 'Bot ' . config('services.discord.bot_token')])->get(
            'https://discord.com/api/guilds/' . config('services.discord.guild_id') . '/channels'
        )->json();

        return collect($channels)->map(fn($channel) => new Channel($channel['id'], $channel['name']));
    }
}
