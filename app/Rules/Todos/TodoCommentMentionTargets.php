<?php

namespace App\Rules\Todos;

use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoMentionCandidateQuery;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class TodoCommentMentionTargets implements ValidationRule
{
    public function __construct(
        private readonly User $actor,
        private readonly Todo $todo,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('todos.comments.validation.mention_targets')->translate();

            return;
        }

        $invalidId = collect($value)
            ->contains(fn (mixed $id): bool => filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false);

        if ($invalidId) {
            $fail('todos.comments.validation.mention_targets')->translate();

            return;
        }

        $submittedIds = collect($value)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($submittedIds->isEmpty()) {
            return;
        }

        $allowedIds = app(TodoMentionCandidateQuery::class)
            ->eligibleUsersFor($this->actor, $this->todo)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id);

        if ($submittedIds->diff($allowedIds)->isNotEmpty()) {
            $fail('todos.comments.validation.mention_targets')->translate();
        }
    }
}
