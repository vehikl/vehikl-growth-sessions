<?php

namespace App\Services\Discord;

use App\Services\Discord\Models\Channel;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;

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
        $channels = json_decode($this->http->get(
            'https://discord.com/api/guilds/' . config('services.discord.guild_id') . '/channels',
            ['headers' => ['Authorization' => 'Bot ' . config('services.discord.bot_token')]]
        )->getBody()->getContents());

        return collect($channels)->map(fn($channel) => new Channel($channel->id, $channel->name));
    }
}
