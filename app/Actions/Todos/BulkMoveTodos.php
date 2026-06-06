<?php

namespace App\Actions\Todos;

use App\Data\Todos\BulkActionResult;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Moves the user's selected tasks to an owned active project, or to no project.
 *
 * This action never changes ownership, lifecycle state, priority, tags, or due
 * dates. Target project validation happens before this action is called; the
 * action still re-scopes selected tasks and authorizes each update.
 */
final class BulkMoveTodos
{
    /**
     * @param  list<int>  $ids
     */
    public function handle(User $user, array $ids, ?int $projectId): BulkActionResult
    {
        if ($ids === []) {
            return BulkActionResult::fromIds([], affected: 0);
        }

        $todos = $user->todos()
            ->whereKey($ids)
            ->get(['id', 'user_id']);

        $todos->each(fn (Todo $todo) => Gate::forUser($user)->authorize('update', $todo));

        $affected = $user->todos()
            ->whereKey($todos->modelKeys())
            ->update(['project_id' => $projectId]);

        return BulkActionResult::fromIds($ids, affected: $affected);
    }
}
