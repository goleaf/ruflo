<?php

namespace App\Rules\Projects;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ProjectInvitationExpiryDays implements ValidationRule
{
    public const MinimumDays = 1;

    public const MaximumDays = 30;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::normalize($value) === null) {
            $fail('todos.collaboration.invites.validation.expires_in_days')->translate();
        }
    }

    public static function normalize(mixed $value): ?int
    {
        if (! is_int($value) && ! ctype_digit((string) $value)) {
            return null;
        }

        $days = (int) $value;

        if ($days < self::MinimumDays || $days > self::MaximumDays) {
            return null;
        }

        return $days;
    }
}
