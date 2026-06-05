<?php

namespace App\Actions\Todos;

use App\Events\CompletedTodosCleared;
use App\Models\User;

/**
 * Soft-deletes the user's completed, non-archived tasks (the Completed tab).
 *
 * Archived tasks are never touched here — clearing completed work and managing
 * the archive are separate concerns. Deletion is soft, so cleared tasks are not
 * destroyed.
 */
final class ClearCompletedTodos
{
    public function handle(User $user): int
    {
        $deletedCount = $user->todos()
            ->completed()
            ->delete();

        if ($deletedCount > 0) {
            CompletedTodosCleared::dispatch($user, $deletedCount);
        }

        return $deletedCount;
    }
}
