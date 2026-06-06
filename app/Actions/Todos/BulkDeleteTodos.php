<?php

namespace App\Actions\Todos;

use App\Data\Todos\BulkActionResult;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Soft-deletes the user's tasks among the selected ids.
 *
 * Re-scoped to the user's own tasks so foreign ids are silently excluded.
 * Deletion is soft (recoverable by design); returns result counts.
 */
final class BulkDeleteTodos
{
    public function __construct(
        private readonly DeleteTodo $deleteTodo,
    ) {}

    /**
     * @param  list<int>  $ids
     */
    public function handle(User $user, array $ids): BulkActionResult
    {
        if ($ids === []) {
            return BulkActionResult::fromIds([], affected: 0);
        }

        $todos = $user->todos()
            ->whereKey($ids)
            ->get(['id', 'user_id']);

        $todos->each(fn (Todo $todo) => Gate::forUser($user)->authorize('delete', $todo));

        $todos->each(fn (Todo $todo) => $this->deleteTodo->handle($todo));

        return BulkActionResult::fromIds($ids, affected: $todos->count());
    }
}
