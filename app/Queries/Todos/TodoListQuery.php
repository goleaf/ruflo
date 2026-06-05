<?php

namespace App\Queries\Todos;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The single owner-scoped read boundary for todos.
 *
 * Every todo list, lookup, and counter in the application must originate here
 * so that no query can ever observe another user's private data. Ownership is
 * applied through the {@see Todo::scopeOwnedBy()} concern.
 */
final class TodoListQuery
{
    /**
     * Base owner-scoped query for the user's visible (non-deleted) todos.
     *
     * @return Builder<Todo>
     */
    public function visibleFor(User $user): Builder
    {
        return Todo::query()
            ->select(['id', 'user_id', 'title', 'is_completed', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->latest();
    }

    /**
     * Resolve a single todo the user is allowed to see.
     *
     * Client-supplied IDs are untrusted: resolving through the owner-scoped
     * query means another user's ID yields a not-found result instead of
     * leaking the record's existence.
     */
    public function findVisibleFor(User $user, int $todoId): Todo
    {
        return $this->visibleFor($user)->findOrFail($todoId);
    }

    /**
     * Aggregate counts for the user's workspace in a single scoped query.
     *
     * @return array{remaining: int, completed: int}
     */
    public function summaryFor(User $user): array
    {
        $summary = Todo::query()
            ->ownedBy($user)
            ->selectRaw('sum(case when is_completed = 0 then 1 else 0 end) as remaining_count')
            ->selectRaw('sum(case when is_completed = 1 then 1 else 0 end) as completed_count')
            ->first();

        return [
            'remaining' => (int) $summary->remaining_count,
            'completed' => (int) $summary->completed_count,
        ];
    }
}
