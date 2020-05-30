<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialMobRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'topic' => 'required|string',
            'location' => 'required|string',
            'start_time' => 'required|date_format:h:i a',
            'end_time' => 'sometimes|required|after:start_time|date_format:h:i a',
            'date' => 'required|date',
        ];
    }
}
