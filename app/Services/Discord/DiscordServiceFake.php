<?php

namespace App\Services\Discord;

use App\Services\Discord\Models\Channel;
use Illuminate\Support\Collection;

class DiscordServiceFake extends DiscordService
{
    public function getChannels(): Collection
    {
        return collect()->times(10)->map(fn ($_, $index) => new Channel($index, "channel-{$index}"));
    }

}
