<?php

namespace App\Rules\Habits;

use App\Enums\HabitFrequency;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class HabitTargetCount implements ValidationRule
{
    public function __construct(
        private readonly ?HabitFrequency $frequency = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || (string) (int) $value !== (string) $value) {
            $fail('habits.validation.target_count')->translate();

            return;
        }

        $count = (int) $value;

        if ($this->frequency === HabitFrequency::Daily && $count !== 1) {
            $fail('habits.validation.target_daily')->translate();

            return;
        }

        if ($count < 1 || $count > 7) {
            $fail('habits.validation.target_count')->translate();
        }
    }
}
