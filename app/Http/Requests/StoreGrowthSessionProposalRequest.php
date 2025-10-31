<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGrowthSessionProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\GrowthSessionProposal::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'topic' => 'required|string',
            'tags' => 'sometimes|array',
            'tags.*' => 'integer|exists:tags,id',
            'time_preferences' => 'required|array|min:1',
            'time_preferences.*.weekday' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'time_preferences.*.start_time' => 'required|date_format:H:i',
            'time_preferences.*.end_time' => 'required|date_format:H:i|after:time_preferences.*.start_time',
        ];
    }
}
