<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCommentRequest extends FormRequest
{
    public function authorize()
    {
        return $this->comment->user->is($this->user());
    }

    public function rules()
    {
        return [];
    }
}
