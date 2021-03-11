<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class DiscordChannelCollection extends ResourceCollection
{
    /** @var string */
    public $collects = DiscordChannel::class;

    public function toArray($request)
    {
        return $this->collection
            ->sortBy(fn(DiscordChannel $discordChannel) => strtolower($discordChannel->resource->name))
            ->toArray();
    }
}
