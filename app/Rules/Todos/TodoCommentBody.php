<?php

namespace App\Rules\Todos;

use App\Data\Todos\TodoCommentData;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class TodoCommentBody implements ValidationRule
{
    public const int MAX_LENGTH = 2000;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('todos.comments.validation.body_required')->translate();

            return;
        }

        $normalized = TodoCommentData::normalizeBody($value);

        if ($normalized === '') {
            $fail('todos.comments.validation.body_required')->translate();

            return;
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            $fail('todos.comments.validation.body_max')->translate([
                'max' => self::MAX_LENGTH,
            ]);
        }
    }
}
