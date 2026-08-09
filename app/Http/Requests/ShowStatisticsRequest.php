<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The statistics range, resolved from the query string.
 *
 * Deliberately sanitizes rather than rejects: a mangled or stale link should still land the
 * member on a usable page. What it will not do is let a value that is not a real calendar day
 * through — `strtotime('2020-02-30')` happily normalises to March 1st, which would put a date
 * nobody asked for into the cache key and the `whereBetween`.
 */
class ShowStatisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_date' => $this->calendarDate($this->input('start_date')),
            'end_date' => $this->calendarDate($this->input('end_date')),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
        ];
    }

    public function startDate(string $fallback): string
    {
        return $this->input('start_date') ?? $fallback;
    }

    public function endDate(string $fallback): string
    {
        return $this->input('end_date') ?? $fallback;
    }

    /** Anything that is not a real calendar day becomes null, so the caller's fallback wins. */
    private function calendarDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)) {
            return null;
        }

        [, $year, $month, $day] = $parts;

        return checkdate((int) $month, (int) $day, (int) $year) ? $value : null;
    }
}
