<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialMobRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('update', $this->social_mob);
    }

    public function rules()
    {
        return [
            'title' => 'sometimes|required|string|max:45',
            'topic' => 'sometimes|required|string',
            'location' => 'sometimes|required|string',
            'start_time' => 'sometimes|required|date_format:h:i a',
            'end_time' => 'sometimes|required|after:start_time|date_format:h:i a',
            'date' => 'sometimes|required|date|after_or_equal:today',
        ];
    }
}
