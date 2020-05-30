<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialMobRequest extends FormRequest
{
    public function authorize()
    {
        return $this->social_mob->owner->is($this->user()) && now()->diffInDays($this->social_mob->date, false) >= 0;
    }

    public function rules()
    {
        return [
            'topic' => 'sometimes|required|string',
            'location' => 'sometimes|required|string',
            'start_time' => 'sometimes|required|date_format:h:i a',
            'end_time' => 'sometimes|required|after:start_time|date_format:h:i a',
            'date' => 'sometimes|required|date',
        ];
    }
}
