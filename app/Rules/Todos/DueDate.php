<?php

namespace App\Rules\Todos;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class DueDate implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (self::normalize($value) === null) {
            $fail('todos.validation.due_date')->translate();
        }
    }

    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value,
            new DateTimeZone(config('app.timezone')),
        );

        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || self::hasParseErrors($errors) || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date->format('Y-m-d');
    }

    /**
     * @param  array{warning_count: int, error_count: int}|false  $errors
     */
    private static function hasParseErrors(array|false $errors): bool
    {
        return is_array($errors)
            && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
    }
}
