<?php

namespace App\Http\Requests;

use App\AnyDesk;
use App\GrowthSession;
use App\Rules\CannotBeInDuplicatedTimeSlot;
use App\Rules\CannotBeWeekend;
use Illuminate\Foundation\Http\FormRequest;

class StoreGrowthSessionRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('create', GrowthSession::class);
    }

    public function rules()
    {
        $timeValues = $this->only(['start_time', 'end_time', 'date']);
        return [
            'title' => 'required|string|max:45',
            'topic' => 'required|string',
            'location' => 'required|string',
            'start_time' => [
                'required',
                'date_format:h:i a',
                new CannotBeInDuplicatedTimeSlot($this->user(), $timeValues)
            ],
            'end_time' => [
                'sometimes',
                'required',
                'after:start_time|date_format:h:i a',
                new CannotBeInDuplicatedTimeSlot($this->user(), $timeValues)
            ],
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
                new CannotBeWeekend,
                new CannotBeInDuplicatedTimeSlot($this->user(), $timeValues)
            ],
            'attendee_limit' => 'sometimes|integer|min:4',
            'discord_channel_id' => 'sometimes|string',
            'anydesk_id' => 'sometimes|integer|exists:' . AnyDesk::class . ',id',
            'is_public' => 'sometimes|boolean',
        ];
    }
}
