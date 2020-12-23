<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteGrowthSessionRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('delete', $this->social_mob);
    }

    public function rules()
    {
        return [];
    }
}
