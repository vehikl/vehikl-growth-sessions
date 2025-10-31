<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveGrowthSessionProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $proposal = $this->route('proposal');
        return $this->user()->can('approve', $proposal);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:45',
            'topic' => 'required|string',
            'location' => 'required|string',
            'start_time' => 'required|date_format:h:i a',
            'end_time' => 'required|after:start_time|date_format:h:i a',
            'date' => 'required|date|after_or_equal:today',
            'attendee_limit' => 'sometimes|integer|min:2',
            'discord_channel_id' => 'sometimes|string',
            'anydesk_id' => 'sometimes|integer|exists:any_desks,id',
            'is_public' => 'sometimes|boolean',
            'allow_watchers' => 'sometimes|boolean',
        ];
    }
}
