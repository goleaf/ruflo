<?php

namespace App\Actions\Todos;

use App\Data\Todos\TodoData;
use App\Events\TodoUpdated;
use App\Exceptions\InvalidTodoTransition;
use App\Models\Todo;
use App\Models\User;

/**
 * Updates a task's editable details: title, priority, due date, project, tags.
 *
 * Editing never completes, archives, unarchives, or deletes the task. Archived
 * tasks must be unarchived before editing. Project and tag references are
 * re-verified against the owner so a forged request can't cross-link data.
 */
final class UpdateTodo
{
    use ResolvesTodoOrganization;

    public function handle(User $user, Todo $todo, TodoData $data): Todo
    {
        if ($todo->isArchived()) {
            throw InvalidTodoTransition::cannotEditArchived();
        }

        $todo->fill([
            'title' => trim($data->title),
            'priority' => $data->priority,
            'due_date' => $data->dueDate,
        ]);

        // project_id is guarded; assign directly after re-scoping to the owner.
        $todo->project_id = $this->resolveProjectId($user, $data->projectId);
        $todo->save();

        $todo->tags()->sync($this->resolveTagIds($user, $data->tagIds));

        TodoUpdated::dispatch($todo);

        return $todo;
    }
}
