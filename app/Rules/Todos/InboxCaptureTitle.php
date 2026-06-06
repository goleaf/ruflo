<?php

namespace App\Rules\Todos;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

final class InboxCaptureTitle implements ValidationRule
{
    public const MaxLength = 120;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $title = self::normalize($value);

        if ($title === null || mb_strlen($title) > self::MaxLength) {
            $fail('todos.validation.inbox_capture_title')->translate();
        }
    }

    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $title = Str::of($value)->squish();

        return $title->isEmpty() ? null : (string) $title;
    }
}
