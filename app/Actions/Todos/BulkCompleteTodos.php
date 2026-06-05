<?php

namespace App\Actions\Todos;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Completes the user's active tasks among the selected ids.
 *
 * The selection is re-scoped to the user's own active tasks, so foreign or
 * non-actionable ids in the payload are silently excluded — a bulk action can
 * never touch another user's data or complete an archived task. Returns the
 * number of tasks actually changed.
 *
 * @param  list<int>  $ids
 */
final class BulkCompleteTodos
{
    /**
     * @param  list<int>  $ids
     */
    public function handle(User $user, array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $todos = $user->todos()
            ->active()
            ->whereKey($ids)
            ->get(['id', 'user_id']);

        $todos->each(fn (Todo $todo) => Gate::forUser($user)->authorize('complete', $todo));

        return $user->todos()
            ->active()
            ->whereKey($todos->modelKeys())
            ->update(['is_completed' => true]);
    }
}
