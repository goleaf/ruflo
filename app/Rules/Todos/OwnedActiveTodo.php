<?php

namespace App\Rules\Todos;

use App\Models\Todo;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final readonly class OwnedActiveTodo implements ValidationRule
{
    public function __construct(
        private User $user,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
            $fail('todos.validation.owned_active_todo')->translate();

            return;
        }

        $exists = Todo::query()
            ->ownedBy($this->user)
            ->active()
            ->whereKey((int) $value)
            ->exists();

        if (! $exists) {
            $fail('todos.validation.owned_active_todo')->translate();
        }
    }
}
