<?php

namespace App\Actions\Todos;

use App\Events\TodoCompleted;
use App\Exceptions\InvalidTodoTransition;
use App\Models\Todo;

/**
 * Completes an active task.
 *
 * Completion is reversible and is never deletion. Archived tasks must be
 * unarchived before completion. Completing an already-completed task is a no-op
 * so duplicate clicks do not emit duplicate lifecycle events.
 */
final class CompleteTodo
{
    public function handle(Todo $todo): Todo
    {
        if ($todo->isArchived()) {
            throw InvalidTodoTransition::cannotCompleteArchived();
        }

        if ($todo->is_completed) {
            return $todo;
        }

        $todo->is_completed = true;
        $todo->save();

        TodoCompleted::dispatch($todo);

        return $todo;
    }
}
