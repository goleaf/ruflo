<?php

namespace App\Actions\Todos;

use App\Events\TodoArchived;
use App\Models\Todo;

/**
 * Archives a task: removes it from active/completed views without deleting it
 * or changing its completion state. Idempotent — archiving an already-archived
 * task is a no-op.
 */
final class ArchiveTodo
{
    public function handle(Todo $todo): Todo
    {
        if ($todo->isArchived()) {
            return $todo;
        }

        // archived_at is system-controlled (not fillable), so set it directly.
        $todo->archived_at = now();
        $todo->save();

        TodoArchived::dispatch($todo);

        return $todo;
    }
}
