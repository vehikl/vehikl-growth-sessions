<?php

namespace App\Rules;

use App\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class CannotBeInDuplicatedTimeSlot implements Rule
{
    private User $user;
    private array $timeValues;

    public function __construct(User $user, array $timeValues)
    {
        $this->user = $user;

        $this->timeValues = ['date' => $timeValues['date']];

        if (Arr::has($timeValues, 'start_time')) {
            $this->timeValues['start_time'] = Carbon::parse($timeValues['start_time'])->format('H:i');
        }

        if (Arr::has($timeValues, 'end_time')) {
            $this->timeValues['end_time'] = Carbon::parse($timeValues['end_time'])->format('H:i');
        }
    }

    public function passes($attribute, $value)
    {
        $hasGrowthSessionInSameTimeSlot = $this->user->growthSessions()
            ->where($this->timeValues)
            ->exists();

        return !$hasGrowthSessionInSameTimeSlot;
    }

    public function message()
    {
        return 'Another growth session was already created by you in this exact time.';
    }
}
