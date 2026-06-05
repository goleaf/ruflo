<?php

namespace App\Queries\Todos;

use App\Enums\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The single owner-scoped read boundary for todos.
 *
 * Every todo list, lookup, and counter in the application must originate here
 * so that no query can ever observe another user's private data. Ownership is
 * applied through the {@see Todo::scopeOwnedBy()} concern; lifecycle bucketing
 * is applied through the model's active/completed/archived scopes.
 */
final class TodoListQuery
{
    /**
     * Base owner-scoped query for the user's visible (non-deleted) todos.
     *
     * Includes archived rows so a single resolved task can be unarchived; use
     * {@see forStatus()} for the user-facing lists.
     *
     * @return Builder<Todo>
     */
    public function visibleFor(User $user): Builder
    {
        return Todo::query()
            ->select(['id', 'user_id', 'title', 'is_completed', 'archived_at', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->latest();
    }

    /**
     * Owner-scoped query narrowed to a single lifecycle bucket.
     *
     * @return Builder<Todo>
     */
    public function forStatus(User $user, TodoStatus $status): Builder
    {
        return $this->visibleFor($user)->where(
            fn (Builder $query) => match ($status) {
                TodoStatus::Active => $query->active(),
                TodoStatus::Completed => $query->completed(),
                TodoStatus::Archived => $query->archived(),
            }
        );
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
     * - active: not completed and not archived
     * - completed: completed and not archived
     * - archived: archived (regardless of completion)
     *
     * @return array{active: int, completed: int, archived: int}
     */
    public function summaryFor(User $user): array
    {
        $summary = Todo::query()
            ->ownedBy($user)
            ->selectRaw('sum(case when archived_at is null and is_completed = 0 then 1 else 0 end) as active_count')
            ->selectRaw('sum(case when archived_at is null and is_completed = 1 then 1 else 0 end) as completed_count')
            ->selectRaw('sum(case when archived_at is not null then 1 else 0 end) as archived_count')
            ->first();

        return [
            'active' => (int) $summary->active_count,
            'completed' => (int) $summary->completed_count,
            'archived' => (int) $summary->archived_count,
        ];
    }
}
