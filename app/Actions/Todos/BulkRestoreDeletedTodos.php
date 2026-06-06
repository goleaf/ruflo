<?php

namespace App\Actions\Todos;

use App\Data\Todos\BulkActionResult;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Restores the user's trashed tasks among the selected ids.
 *
 * Foreign ids and non-trashed ids are excluded before authorization and
 * mutation. Archive and completion state are preserved after restore.
 */
final class BulkRestoreDeletedTodos
{
    public function __construct(
        private readonly RestoreDeletedTodo $restoreDeletedTodo,
    ) {}

    /**
     * @param  list<int>  $ids
     */
    public function handle(User $user, array $ids): BulkActionResult
    {
        if ($ids === []) {
            return BulkActionResult::fromIds([], affected: 0);
        }

        $todos = Todo::query()
            ->onlyTrashed()
            ->ownedBy($user)
            ->whereKey($ids)
            ->get(['id', 'user_id', 'is_completed', 'archived_at', 'deleted_at']);

        $todos->each(fn (Todo $todo) => Gate::forUser($user)->authorize('restore', $todo));

        $todos->each(fn (Todo $todo) => $this->restoreDeletedTodo->handle($todo));

        return BulkActionResult::fromIds($ids, affected: $todos->count());
    }
}
