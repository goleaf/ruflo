<?php

namespace App\Actions\Todos;

use App\Models\User;

/**
 * Archives the user's non-archived tasks among the selected ids.
 *
 * Re-scoped to the user's own tasks so foreign or already-archived ids are
 * silently excluded. Returns the number of tasks actually archived.
 */
final class BulkArchiveTodos
{
    /**
     * @param  list<int>  $ids
     */
    public function handle(User $user, array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        return $user->todos()->whereNull('archived_at')->whereKey($ids)->update(['archived_at' => now()]);
    }
}
