<?php

namespace App\Actions\Todos;

use App\Actions\Projects\CreateProject;
use App\Data\Projects\ProjectData;
use App\Data\Todos\TodoData;
use App\Models\Project;
use App\Models\Todo;
use App\Models\TodoTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class CreateTodoFromTemplate
{
    public function __construct(
        private readonly CreateProject $createProject,
        private readonly CreateTodo $createTodo,
        private readonly CreateTodoChecklistItem $createChecklistItem,
    ) {}

    public function handle(User $user, TodoTemplate $template): Todo
    {
        Gate::forUser($user)->authorize('instantiate', $template);

        $projectId = $this->resolveProjectId($user, $template->project_name);

        $todo = $this->createTodo->handle($user, new TodoData(
            title: $template->title,
            priority: $template->priority,
            dueDate: $this->dueDateFor($template),
            projectId: $projectId,
        ));

        foreach ($template->checklist_items ?? [] as $itemTitle) {
            $this->createChecklistItem->handle($user, $todo, $itemTitle);
        }

        return $todo->refresh()->load(['project', 'checklistItems']);
    }

    private function resolveProjectId(User $user, ?string $projectName): ?int
    {
        if ($projectName === null || $projectName === '') {
            return null;
        }

        $project = Project::query()
            ->ownedBy($user)
            ->active()
            ->where('name', $projectName)
            ->first();

        if ($project instanceof Project) {
            return $project->id;
        }

        return $this->createProject
            ->handle($user, new ProjectData(name: $projectName))
            ->id;
    }

    private function dueDateFor(TodoTemplate $template): ?string
    {
        if ($template->due_offset_days === null) {
            return null;
        }

        return today()->addDays($template->due_offset_days)->toDateString();
    }
}
