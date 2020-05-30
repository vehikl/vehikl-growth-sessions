<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialMobRequest extends FormRequest
{
    public function authorize()
    {
        return $this->social_mob->owner->is($this->user()) && now()->diffInDays($this->social_mob->start_time, false) >= 0;
    }

    public function rules()
    {
        return [
            'topic' => 'sometimes|required|string',
            'location' => 'sometimes|required|string',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date',
            'date' => 'sometimes|required|date',
        ];
    }
}
