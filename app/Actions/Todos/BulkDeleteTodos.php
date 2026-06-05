<?php

namespace App\Actions\Todos;

use App\Models\User;

/**
 * Soft-deletes the user's tasks among the selected ids.
 *
 * Re-scoped to the user's own tasks so foreign ids are silently excluded.
 * Deletion is soft (recoverable by design); returns the number deleted.
 */
final class BulkDeleteTodos
{
    /**
     * @param  list<int>  $ids
     */
    public function handle(User $user, array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        return $user->todos()->whereKey($ids)->delete();
    }
}
