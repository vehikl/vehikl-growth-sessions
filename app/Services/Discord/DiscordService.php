<?php

namespace App\Services\Discord;

use App\Models\GrowthSession;
use App\Services\Discord\Models\Channel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordService
{
    public function getChannels(): Collection
    {
        if(!config('services.discord.bot_token') || ! config('services.discord.guild_id')) {
            return new Collection();
        }

        $response = Http::withHeaders(['Authorization' => 'Bot ' . config('services.discord.bot_token')])->get(
            'https://discord.com/api/guilds/' . config('services.discord.guild_id') . '/channels'
        );

        // An error body is a JSON object, not a list of channels.
        if ($response->failed()) {
            Log::warning('Unable to fetch Discord channels.', ['status' => $response->status(), 'body' => $response->body()]);

            return new Collection();
        }

        return collect($response->json())
            ->filter(function (array $channel) {
                return ($channel['parent_id'] ?? null) !== config('services.discord.vidya_id')
                    && $channel['id'] !== config('services.discord.vidya_id');
            })
            ->map(fn(array $channel) => new Channel($channel['id'], $channel['name']));
    }

    public function getOccupiedChannels(string $toDateString, ?int $exceptGrowthSessionId = null): Collection
    {
        return GrowthSession::query()
            ->where('date', "=", $toDateString)
            ->whereNotNull('discord_channel_id')
            ->when($exceptGrowthSessionId, fn($query) => $query->whereKeyNot($exceptGrowthSessionId))
            ->get()
            ->map(fn(GrowthSession $gs) => new Channel($gs->discord_channel_id, ''));
    }
}
