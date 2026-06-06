<?php

namespace App\Rules\Projects;

use App\Enums\ProjectRole;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class ProjectMemberRole implements ValidationRule
{
    public static function isValid(mixed $value): bool
    {
        return is_string($value) && in_array($value, ProjectRole::assignableValues(), true);
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid($value)) {
            $fail('todos.collaboration.members.validation.role')->translate();
        }
    }
}
