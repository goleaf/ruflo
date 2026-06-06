<?php

namespace App\Rules\Todos;

use App\Models\Todo;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class OwnedTodo implements ValidationRule
{
    public function __construct(
        private readonly User $user,
        private readonly bool $onlyTrashed = false,
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

        $query = Todo::query()
            ->whereKey((int) $value)
            ->whereBelongsTo($this->user);

        if ($this->onlyTrashed) {
            $query->onlyTrashed();
        }

        $exists = $query->exists();

        if (! $exists) {
            $fail($this->onlyTrashed ? 'todos.validation.owned_deleted_todo' : 'todos.validation.owned_todo')->translate();
        }
    }
}
