<?php

namespace App\Rules\Todos;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class TimeEntryDuration implements ValidationRule
{
    public const int MinMinutes = 1;

    public const int MaxMinutes = 1440;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $minutes = self::normalizeMinutes($value);

        if ($minutes === null || $minutes < self::MinMinutes || $minutes > self::MaxMinutes) {
            $fail('todos.validation.time_entry_duration')->translate();
        }
    }

    public static function normalizeMinutes(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }
}
