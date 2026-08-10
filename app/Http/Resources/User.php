<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class User extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'github_nickname' => $this->github_nickname,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'is_vehikl_member' => $this->is_vehikl_member,
        ];
    }
}
