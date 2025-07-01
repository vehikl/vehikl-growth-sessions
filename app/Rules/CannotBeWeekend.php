<?php

namespace App\Rules;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CannotBeWeekend implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (CarbonImmutable::parse($value)->isWeekend()) {
            $fail('A growth session can not be hosted on weekends.');
        }
    }
}
