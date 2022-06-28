<?php

namespace App\Rules;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Rule;

class CannotBeWeekend implements Rule
{
    public function passes($attribute, $value)
    {
        return !CarbonImmutable::parse($value)->isWeekend();
    }

    public function message()
    {
        return 'A growth session can not be hosted on weekends.';
    }
}
