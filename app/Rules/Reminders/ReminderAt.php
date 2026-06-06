<?php

namespace App\Rules\Reminders;

use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Translation\PotentiallyTranslatedString;

final class ReminderAt implements ValidationRule
{
    /**
     * Parse a browser `datetime-local` value into app-timezone Carbon.
     */
    public static function parse(string $value): Carbon
    {
        foreach (['Y-m-d\TH:i', 'Y-m-d H:i', 'Y-m-d\TH:i:s'] as $format) {
            if (! Carbon::hasFormat($value, $format)) {
                continue;
            }

            try {
                $date = Carbon::createFromFormat($format, $value, config('app.timezone'));
            } catch (InvalidFormatException) {
                continue;
            }

            if ($date instanceof Carbon) {
                return $date->startOfMinute();
            }
        }

        throw new InvalidFormatException('Invalid reminder timestamp.');
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('reminders.validation.remind_at')->translate();

            return;
        }

        try {
            $remindAt = self::parse($value);
        } catch (InvalidFormatException) {
            $fail('reminders.validation.remind_at')->translate();

            return;
        }

        if ($remindAt->lessThan(now()->subMinute())) {
            $fail('reminders.validation.remind_at_future')->translate();
        }
    }
}
