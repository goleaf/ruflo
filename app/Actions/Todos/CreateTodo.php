<?php

namespace App\Actions\Todos;

use App\Data\Todos\TodoData;
use App\Events\TodoCreated;
use App\Models\Todo;
use App\Models\User;

/**
 * Creates a task in the user's workspace.
 *
 * Ownership is assigned through the relationship. The project and tags carried
 * by the DTO are re-verified against the user (via {@see ResolvesTodoOrganization})
 * so a crafted request can never attach a task to another user's project or tag.
 */
final class CreateTodo
{
    use ResolvesTodoOrganization;

    public function handle(User $user, TodoData $data): Todo
    {
        $todo = $user->todos()->make([
            'title' => $data->title,
            'priority' => $data->priority,
            'due_date' => $data->dueDate,
        ]);

        // project_id is guarded (not fillable); assign it directly only after
        // re-scoping it to the user, never from raw mass-assigned input.
        $todo->project_id = $this->resolveProjectId($user, $data->projectId);
        $todo->save();

        $todo->tags()->sync($this->resolveTagIds($user, $data->tagIds));

        TodoCreated::dispatch($todo);

        return $todo;
    }
}
