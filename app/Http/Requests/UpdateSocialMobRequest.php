<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialMobRequest extends FormRequest
{
    public function authorize()
    {
        return $this->social_mob->owner->is($this->user());
    }

    public function rules()
    {
        return [
            'topic' => 'sometimes|required|string',
            'location' => 'sometimes|required|string',
            'start_time' => 'sometimes|required|date'
        ];
    }
}
