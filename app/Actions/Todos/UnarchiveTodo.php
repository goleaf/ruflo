<?php

namespace App\Actions\Todos;

use App\Events\TodoUnarchived;
use App\Models\Todo;

/**
 * Unarchives a task from the archive.
 *
 * Completion state is intentionally preserved: a task archived while completed
 * returns to the completed bucket, and one archived while active returns to
 * active. Idempotent — unarchiving a non-archived task is a no-op.
 */
final class UnarchiveTodo
{
    public function handle(Todo $todo): Todo
    {
        if (! $todo->isArchived()) {
            return $todo;
        }

        // archived_at is system-controlled (not fillable), so set it directly.
        $todo->archived_at = null;
        $todo->save();

        TodoUnarchived::dispatch($todo);

        return $todo;
    }
}
