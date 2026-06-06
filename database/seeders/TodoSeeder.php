<?php

namespace Database\Seeders;

use App\Data\Todos\SavedTodoViewData;
use App\Enums\Priority;
use App\Models\Project;
use App\Models\SavedTodoView;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic, isolated todo workspaces.
 *
 * Every user gets projects, tags, and tasks across the full range of states
 * (active, due today, overdue, upcoming, high priority, completed, archived,
 * trashed) so that ownership, filters, due-date buckets, and bulk actions can
 * be exercised by hand and so permission bugs cannot hide behind thin
 * single-user data.
 */
class TodoSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->each(function (User $user): void {
            $this->seedWorkspace($user);
        });
    }

    private function seedWorkspace(User $user): void
    {
        $work = $this->upsertProject($user, 'Work', 'blue');
        $home = $this->upsertProject($user, 'Home', 'green');
        $this->upsertProject($user, 'Old plans', 'zinc', archived: true);

        $urgent = $this->upsertTag($user, 'urgent', 'red');
        $waiting = $this->upsertTag($user, 'waiting', 'amber');

        $reviewFlow = $this->upsertTodo($user, 'Review the current flow', [
            'project_id' => $work->id,
            'priority' => Priority::High,
            'due_date' => today()->toDateString(),
        ], $urgent);
        $this->upsertChecklist($reviewFlow, [
            ['title' => 'Confirm the owner-scoped route', 'completed' => true],
            ['title' => 'Check the empty state copy'],
            ['title' => 'Record the next UI note'],
        ]);

        $overdueReport = $this->upsertTodo($user, 'Send the overdue report', [
            'project_id' => $work->id,
            'priority' => Priority::Urgent,
            'due_date' => today()->subDay()->toDateString(),
        ], $urgent, $waiting);
        $this->upsertChecklist($overdueReport, [
            ['title' => 'Pull the latest metrics'],
            ['title' => 'Send the short summary'],
        ]);

        $weekend = $this->upsertTodo($user, 'Plan the weekend', [
            'project_id' => $home->id,
            'priority' => Priority::Normal,
            'due_date' => today()->addDays(3)->toDateString(),
        ]);
        $this->upsertChecklist($weekend, [
            ['title' => 'Pick one outdoor plan'],
            ['title' => 'Reserve a quiet block'],
        ]);

        $this->upsertTodo($user, 'Capture a loose idea', [
            'priority' => Priority::Low,
        ]);

        $this->upsertTodo($user, 'Ship one small improvement', [
            'project_id' => $work->id,
            'is_completed' => true,
            'priority' => Priority::Normal,
        ]);

        $archivedChecklist = $this->upsertTodo($user, 'Last month\'s checklist', [
            'project_id' => $home->id,
            'priority' => Priority::Normal,
            'archived_at' => now(),
        ]);
        $this->upsertChecklist($archivedChecklist, [
            ['title' => 'Review completed notes', 'completed' => true],
            ['title' => 'Keep archived context visible', 'completed' => true],
        ]);

        $this->upsertTodo($user, 'Archived completed launch notes', [
            'project_id' => $work->id,
            'is_completed' => true,
            'priority' => Priority::High,
            'archived_at' => now(),
        ], $waiting);

        $this->upsertTodo($user, 'Removed duplicate task', [
            'project_id' => $home->id,
            'priority' => Priority::Low,
            'deleted_at' => now()->subDays(2),
        ]);

        $this->upsertSavedView($user, 'Today focus', [
            'due' => 'today',
            'sort' => 'due',
            'direction' => 'asc',
        ]);

        $this->upsertSavedView($user, 'Urgent work', [
            'project' => (string) $work->id,
            'priorityFilter' => Priority::Urgent->value,
            'sort' => 'priority',
            'direction' => 'desc',
        ]);

        $this->upsertSavedView($user, 'Waiting on others', [
            'tag' => (string) $waiting->id,
            'sort' => 'updated',
            'direction' => 'desc',
        ]);

        $this->upsertTemplate($user, 'Daily planning routine', [
            'kind' => 'routine',
            'visibility' => 'private',
            'title' => 'Plan today',
            'description' => 'A short routine for picking the next useful tasks.',
            'priority' => Priority::High,
            'due_offset_days' => 0,
            'project_name' => 'Work',
            'checklist_items' => [
                'Review overdue tasks',
                'Pick three priorities',
                'Block one focused session',
            ],
        ]);

        $this->upsertTemplate($user, 'Project kickoff', [
            'kind' => 'project',
            'visibility' => 'shared',
            'title' => 'Start the project kickoff',
            'description' => 'Creates a project-backed kickoff task with starter checklist items.',
            'priority' => Priority::Normal,
            'due_offset_days' => 3,
            'project_name' => 'Project kickoff',
            'checklist_items' => [
                'Confirm the goal',
                'List the first milestones',
                'Choose the next owner action',
            ],
        ]);

        $this->upsertTemplate($user, 'Bug triage checklist', [
            'kind' => 'checklist',
            'visibility' => 'private',
            'title' => 'Triage a bug report',
            'description' => 'A repeatable checklist for turning an issue into clear next work.',
            'priority' => Priority::Urgent,
            'due_offset_days' => null,
            'project_name' => 'Work',
            'checklist_items' => [
                'Reproduce the report',
                'Capture expected behavior',
                'Decide fix or backlog',
            ],
        ]);
    }

    private function upsertProject(User $user, string $name, string $color, bool $archived = false): Project
    {
        $project = Project::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first() ?? new Project;

        $project->forceFill([
            'user_id' => $user->id,
            'name' => $name,
            'color' => $color,
            'archived_at' => $archived ? ($project->archived_at ?? now()) : null,
        ])->save();

        return $project;
    }

    private function upsertTag(User $user, string $name, string $color): Tag
    {
        $tag = Tag::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first() ?? new Tag;

        $tag->forceFill([
            'user_id' => $user->id,
            'name' => $name,
            'color' => $color,
        ])->save();

        return $tag;
    }

    /**
     * @param  array{project_id?: int, priority?: Priority, due_date?: string|null, is_completed?: bool, archived_at?: mixed, deleted_at?: mixed}  $attributes
     */
    private function upsertTodo(User $user, string $title, array $attributes, Tag ...$tags): Todo
    {
        $todo = Todo::query()
            ->withTrashed()
            ->where('user_id', $user->id)
            ->where('title', $title)
            ->first() ?? new Todo;

        $todo->forceFill([
            'user_id' => $user->id,
            'title' => $title,
            'project_id' => $attributes['project_id'] ?? null,
            'priority' => $attributes['priority'] ?? Priority::Normal,
            'due_date' => $attributes['due_date'] ?? null,
            'is_completed' => $attributes['is_completed'] ?? false,
            'archived_at' => $attributes['archived_at'] ?? null,
            'deleted_at' => $attributes['deleted_at'] ?? null,
        ])->save();

        $todo->tags()->sync(collect($tags)->pluck('id')->all());

        return $todo;
    }

    /**
     * @param  list<array{title: string, completed?: bool}>  $items
     */
    private function upsertChecklist(Todo $todo, array $items): void
    {
        foreach ($items as $index => $itemData) {
            $completed = $itemData['completed'] ?? false;

            $item = TodoChecklistItem::query()
                ->where('todo_id', $todo->id)
                ->where('title', $itemData['title'])
                ->first() ?? new TodoChecklistItem;

            $item->forceFill([
                'user_id' => $todo->user_id,
                'todo_id' => $todo->id,
                'title' => $itemData['title'],
                'is_completed' => $completed,
                'completed_at' => $completed ? ($item->completed_at ?? now()) : null,
                'position' => $index + 1,
            ])->save();
        }
    }

    /**
     * @param  array{kind: string, visibility: string, title: string, description: string, priority: Priority, due_offset_days?: int|null, project_name?: string|null, checklist_items: list<string>}  $attributes
     */
    private function upsertTemplate(User $user, string $name, array $attributes): TodoTemplate
    {
        $template = TodoTemplate::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first() ?? new TodoTemplate;

        $template->forceFill([
            'user_id' => $user->id,
            'name' => $name,
            'kind' => $attributes['kind'],
            'visibility' => $attributes['visibility'],
            'title' => $attributes['title'],
            'description' => $attributes['description'],
            'priority' => $attributes['priority'],
            'due_offset_days' => $attributes['due_offset_days'] ?? null,
            'project_name' => $attributes['project_name'] ?? null,
            'checklist_items' => $attributes['checklist_items'],
        ])->save();

        return $template;
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function upsertSavedView(User $user, string $name, array $criteria): SavedTodoView
    {
        $savedView = SavedTodoView::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first() ?? new SavedTodoView;

        $savedView->forceFill([
            'user_id' => $user->id,
            'name' => $name,
            'criteria' => SavedTodoViewData::normalizeCriteria($criteria),
        ])->save();

        return $savedView;
    }
}
