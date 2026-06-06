<?php

namespace App\Rules\Todos;

use App\Enums\TodoStatus;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class BoardStatus implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('todos.validation.board_status')->translate();

            return;
        }

        $status = TodoStatus::tryFrom($value);

        if (! in_array($status, [TodoStatus::Active, TodoStatus::Completed, TodoStatus::Archived], true)) {
            $fail('todos.validation.board_status')->translate();
        }
    }
}
