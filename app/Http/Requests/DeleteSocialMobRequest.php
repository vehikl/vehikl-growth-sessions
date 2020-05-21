<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteSocialMobRequest extends FormRequest
{
    public function authorize()
    {
        return $this->social_mob->owner->is($this->user());
    }

    public function rules()
    {
        return [];
    }
}
