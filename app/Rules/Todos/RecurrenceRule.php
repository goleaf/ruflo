<?php

namespace App\Rules\Todos;

use App\Data\Todos\RecurrenceRuleData;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\ValidationException;

final class RecurrenceRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('todos.validation.recurrence_rule')->translate();

            return;
        }

        try {
            RecurrenceRuleData::fromPayload($value);
        } catch (ValidationException $exception) {
            $fail('todos.validation.recurrence_rule')->translate();
        }
    }
}
