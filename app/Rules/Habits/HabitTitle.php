<?php

namespace App\Rules\Habits;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class HabitTitle implements ValidationRule
{
    public const int MaxLength = 120;

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > self::MaxLength) {
            $fail('habits.validation.title')->translate();
        }
    }
}
