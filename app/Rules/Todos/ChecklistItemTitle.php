<?php

namespace App\Rules\Todos;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

final class ChecklistItemTitle implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || Str::of($value)->squish()->isEmpty()) {
            $fail('todos.validation.checklist_item_title')->translate();
        }
    }
}
