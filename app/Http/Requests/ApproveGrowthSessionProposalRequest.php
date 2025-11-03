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

    protected function prepareForValidation()
    {
        if ($this->has('title') && is_string($this->title)) {
            $this->merge([
                'title' => substr($this->title, 0, 45),
            ]);
        }
    }

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
            'anydesk_id' => 'sometimes|integer|exists:anydesks,id',
            'is_public' => 'sometimes|boolean',
            'allow_watchers' => 'sometimes|boolean',
        ];
    }
}
