<?php

namespace App\Actions\Todos;

use App\Events\TodoCompletionToggled;
use App\Exceptions\InvalidTodoTransition;
use App\Models\Todo;

/**
 * Flips a task between active and completed.
 *
 * Completion is reversible and is never deletion. Archived tasks are not a
 * valid source state: they must be restored first, so toggling one is rejected.
 */
final class ToggleTodoCompletion
{
    public function handle(Todo $todo): Todo
    {
        if ($todo->isArchived()) {
            throw InvalidTodoTransition::cannotToggleArchived();
        }

        $todo->update([
            'is_completed' => ! $todo->is_completed,
        ]);

        TodoCompletionToggled::dispatch($todo);

        return $todo;
    }
}
