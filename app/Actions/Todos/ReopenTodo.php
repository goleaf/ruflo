<?php

namespace App\Actions\Todos;

use App\Events\TodoReopened;
use App\Exceptions\InvalidTodoTransition;
use App\Models\Todo;

/**
 * Reopens a completed task back to active work.
 *
 * Reopening preserves all editable details. Archived tasks must be unarchived
 * before reopening. Reopening an already-active task is a no-op so duplicate
 * clicks do not emit duplicate lifecycle events.
 */
final class ReopenTodo
{
    public function handle(Todo $todo): Todo
    {
        if ($todo->isArchived()) {
            throw InvalidTodoTransition::cannotReopenArchived();
        }

        if (! $todo->is_completed) {
            return $todo;
        }

        $todo->is_completed = false;
        $todo->save();

        TodoReopened::dispatch($todo);

        return $todo;
    }
}
