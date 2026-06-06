<?php

namespace App\Actions\Todos;

use App\Data\Todos\TodoData;
use App\Enums\TodoTransition;
use App\Events\TodoUpdated;
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

    public function __construct(
        private readonly TodoLifecycleStateMachine $stateMachine,
    ) {}

    public function handle(User $user, Todo $todo, TodoData $data): Todo
    {
        $this->stateMachine->assertCan($todo, TodoTransition::Update);

        $originalActivityValues = $this->activityValues($todo);

        $todo->fill([
            'title' => trim($data->title),
            'priority' => $data->priority,
            'due_date' => $data->dueDate,
        ]);

        // project_id is guarded; assign directly after re-scoping to the owner.
        $todo->project_id = $this->resolveProjectId($user, $data->projectId);
        $todo->save();

        $todo->tags()->sync($this->resolveTagIds($user, $data->tagIds));

        TodoUpdated::dispatch($todo, $this->activityChanges($originalActivityValues, $this->activityValues($todo)));

        return $todo;
    }

    /**
     * @return array{
     *     title: string,
     *     priority: string,
     *     due_date: ?string,
     *     project_id: ?int,
     *     tag_ids: list<int>
     * }
     */
    private function activityValues(Todo $todo): array
    {
        return [
            'title' => trim($todo->title),
            'priority' => $todo->priority->value,
            'due_date' => $todo->due_date?->toDateString(),
            'project_id' => $todo->project_id === null ? null : (int) $todo->project_id,
            'tag_ids' => $todo->tags()
                ->pluck('tags.id')
                ->map(fn (mixed $id): int => (int) $id)
                ->sort()
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function activityChanges(array $oldValues, array $newValues): array
    {
        $changes = [];

        foreach ($newValues as $field => $newValue) {
            $oldValue = $oldValues[$field] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }
}
