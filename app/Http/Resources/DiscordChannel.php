<?php

namespace App\Http\Resources;

use App\Services\Discord\Models\Channel;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class DiscordChannel
 * @property Channel $resource
 */
class DiscordChannel extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
        ];
    }
}
