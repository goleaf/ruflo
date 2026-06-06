<?php

namespace App\Rules\Todos;

use Carbon\CarbonImmutable;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class CalendarMonth implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::normalize($value) === null) {
            $fail('todos.validation.calendar_month')->translate();
        }
    }

    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m',
            $value,
            new DateTimeZone(config('app.timezone')),
        );

        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || self::hasParseErrors($errors) || $date->format('Y-m') !== $value) {
            return null;
        }

        return $date->format('Y-m');
    }

    public static function toMonth(mixed $value): ?CarbonImmutable
    {
        $normalized = self::normalize($value);

        if ($normalized === null) {
            return null;
        }

        $month = CarbonImmutable::createFromFormat('!Y-m', $normalized, config('app.timezone'));

        if ($month === null || $month === false) {
            return null;
        }

        return $month->startOfMonth();
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
