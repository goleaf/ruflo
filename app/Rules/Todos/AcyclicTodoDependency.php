<?php

namespace App\Rules\Todos;

use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoDependencyQuery;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\ValidationException;

final readonly class AcyclicTodoDependency implements ValidationRule
{
    public function __construct(
        private User $user,
        private Todo $todo,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
            $fail('todos.validation.todo_dependency')->translate();

            return;
        }

        try {
            $candidate = app(TodoDependencyQuery::class)->findCandidateFor($this->user, $this->todo, (int) $value);
            app(TodoDependencyQuery::class)->ensureCanAttach($this->user, $this->todo, $candidate, $attribute);
        } catch (ModelNotFoundException|ValidationException) {
            $fail('todos.validation.todo_dependency')->translate();
        }
    }
}
