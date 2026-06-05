<?php

namespace App\Actions\Todos;

use App\Data\Todos\TodoData;
use App\Events\TodoUpdated;
use App\Exceptions\InvalidTodoTransition;
use App\Models\Todo;

/**
 * Updates a task's editable details.
 *
 * Editing only ever changes user-entered content (the title) — it never
 * completes, archives, restores, or deletes the task. Archived tasks must be
 * restored before editing so the user is never editing something hidden.
 */
final class UpdateTodo
{
    public function handle(Todo $todo, TodoData $data): Todo
    {
        if ($todo->isArchived()) {
            throw InvalidTodoTransition::cannotEditArchived();
        }

        $todo->update([
            'title' => $data->title,
        ]);

        TodoUpdated::dispatch($todo);

        return $todo;
    }
}
