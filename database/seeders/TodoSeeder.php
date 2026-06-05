<?php

namespace Database\Seeders;

use App\Enums\Priority;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic, isolated todo workspaces.
 *
 * Every user gets projects, tags, and tasks across the full range of states
 * (active, due today, overdue, upcoming, high priority, completed, archived) so
 * that ownership, filters, due-date buckets, and bulk actions can be exercised
 * by hand and so permission bugs cannot hide behind thin single-user data.
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

        $this->upsertTodo($user, 'Review the current flow', [
            'project_id' => $work->id,
            'priority' => Priority::High,
            'due_date' => today()->toDateString(),
        ], $urgent);

        $this->upsertTodo($user, 'Send the overdue report', [
            'project_id' => $work->id,
            'priority' => Priority::Urgent,
            'due_date' => today()->subDay()->toDateString(),
        ], $urgent, $waiting);

        $this->upsertTodo($user, 'Plan the weekend', [
            'project_id' => $home->id,
            'priority' => Priority::Normal,
            'due_date' => today()->addDays(3)->toDateString(),
        ]);

        $this->upsertTodo($user, 'Capture a loose idea', [
            'priority' => Priority::Low,
        ]);

        $this->upsertTodo($user, 'Ship one small improvement', [
            'project_id' => $work->id,
            'is_completed' => true,
            'priority' => Priority::Normal,
        ]);

        $this->upsertTodo($user, 'Last month\'s checklist', [
            'project_id' => $home->id,
            'priority' => Priority::Normal,
            'archived_at' => now(),
        ]);

        $this->upsertTodo($user, 'Archived completed launch notes', [
            'project_id' => $work->id,
            'is_completed' => true,
            'priority' => Priority::High,
            'archived_at' => now(),
        ], $waiting);
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
     * @param  array{project_id?: int, priority?: Priority, due_date?: string|null, is_completed?: bool, archived_at?: mixed}  $attributes
     */
    private function upsertTodo(User $user, string $title, array $attributes, Tag ...$tags): Todo
    {
        $todo = Todo::query()
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
            'deleted_at' => null,
        ])->save();

        $todo->tags()->sync(collect($tags)->pluck('id')->all());

        return $todo;
    }
}
