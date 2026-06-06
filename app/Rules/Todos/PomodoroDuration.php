<?php

namespace App\Rules\Todos;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class PomodoroDuration implements ValidationRule
{
    /**
     * @var list<int>
     */
    public const array Options = [15, 25, 50];

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! ctype_digit((string) $value)) {
            $fail('todos.validation.pomodoro_duration')->translate();

            return;
        }

        if (! in_array((int) $value, self::Options, true)) {
            $fail('todos.validation.pomodoro_duration')->translate();
        }
    }
}
