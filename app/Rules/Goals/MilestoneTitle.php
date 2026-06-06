<?php

namespace App\Rules\Goals;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class MilestoneTitle implements ValidationRule
{
    public const int MaxLength = 120;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || Str::of($value)->squish()->isEmpty()) {
            $fail('goals.validation.milestone_title')->translate();
        }
    }
}
