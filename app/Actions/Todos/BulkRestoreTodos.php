<?php

namespace App\Actions\Todos;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Restores the user's archived tasks among the selected ids.
 *
 * Foreign ids and non-archived ids are excluded before authorization and
 * mutation. Completion state is preserved, matching single-task restore.
 */
final class BulkRestoreTodos
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
            ->archived()
            ->whereKey($ids)
            ->get(['id', 'user_id']);

        $todos->each(fn (Todo $todo) => Gate::forUser($user)->authorize('restore', $todo));

        return $user->todos()
            ->archived()
            ->whereKey($todos->modelKeys())
            ->update(['archived_at' => null]);
    }
}
