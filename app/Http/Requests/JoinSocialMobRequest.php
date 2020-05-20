<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinSocialMobRequest extends FormRequest
{
    public function authorize()
    {
        return ! $this->social_mob->attendees()->find($this->user()->id);
    }

    public function rules()
    {
        return [];
    }
}
