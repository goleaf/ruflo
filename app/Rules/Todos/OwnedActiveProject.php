<?php

namespace App\Rules\Todos;

use App\Models\Project;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class OwnedActiveProject implements ValidationRule
{
    public function __construct(
        private readonly User $user,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! ctype_digit((string) $value)) {
            return;
        }

        $exists = Project::query()
            ->whereKey((int) $value)
            ->whereBelongsTo($this->user)
            ->active()
            ->exists();

        if (! $exists) {
            $fail('todos.validation.owned_active_project')->translate();
        }
    }
}
