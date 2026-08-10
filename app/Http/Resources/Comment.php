<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Comment extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'growth_session_id' => $this->growth_session_id,
            'content' => $this->content,
            'segments' => $this->segments,
            'time_stamp' => $this->time_stamp,
            'user' => new User($this->user),
        ];
    }
}
