<?php

namespace App\Queries\Todos;

use App\Enums\TimeEntryStatus;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\User;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final class TimeEntryQuery
{
    /**
     * @return array{today_seconds: int, week_seconds: int, total_seconds: int, active_seconds: int}
     */
    public function summaryFor(User $user): array
    {
        $base = TimeEntry::query()
            ->ownedBy($user)
            ->completed();

        return [
            'today_seconds' => (int) (clone $base)
                ->whereDate('entry_date', today())
                ->sum('duration_seconds'),
            'week_seconds' => (int) (clone $base)
                ->whereDate('entry_date', '>=', today()->startOfWeek())
                ->sum('duration_seconds'),
            'total_seconds' => (int) (clone $base)
                ->sum('duration_seconds'),
            'active_seconds' => $this->activeFor($user)?->elapsedSeconds() ?? 0,
        ];
    }

    public function activeFor(User $user): ?TimeEntry
    {
        return TimeEntry::query()
            ->ownedBy($user)
            ->active()
            ->with(['todo', 'project'])
            ->latest('updated_at')
            ->latest('id')
            ->first();
    }

    public function findFor(User $user, int $entryId): TimeEntry
    {
        $entry = TimeEntry::query()
            ->ownedBy($user)
            ->with(['todo', 'project'])
            ->find($entryId);

        if ($entry instanceof TimeEntry) {
            return $entry;
        }

        throw (new ModelNotFoundException)->setModel(TimeEntry::class, [$entryId]);
    }

    /**
     * @return Collection<int, TimeEntry>
     */
    public function recentFor(User $user, int $limit = 10): Collection
    {
        return TimeEntry::query()
            ->ownedBy($user)
            ->where('status', TimeEntryStatus::Completed->value)
            ->with(['todo', 'project'])
            ->latest('entry_date')
            ->latest('updated_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Todo>
     */
    public function taskOptionsFor(User $user): Collection
    {
        return Todo::query()
            ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->with(['project' => fn ($query) => $query->whereBelongsTo($user)])
            ->orderByRaw('is_completed asc')
            ->latest('updated_at')
            ->limit(50)
            ->get();
    }

    /**
     * @return Collection<int, Project>
     */
    public function projectOptionsFor(User $user): Collection
    {
        return Project::query()
            ->select(['id', 'user_id', 'name', 'color', 'archived_at', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->active()
            ->orderBy('name')
            ->get();
    }

    public function findTrackableTodoFor(User $user, int $todoId): Todo
    {
        return Todo::query()
            ->ownedBy($user)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->with(['project' => fn ($query) => $query->whereBelongsTo($user)])
            ->findOrFail($todoId);
    }

    public function findActiveProjectFor(User $user, int $projectId): Project
    {
        return Project::query()
            ->ownedBy($user)
            ->active()
            ->findOrFail($projectId);
    }

    /**
     * @return array{todo: Todo|null, project: Project|null}
     */
    public function resolveContext(User $user, ?int $todoId, ?int $projectId): array
    {
        if ($todoId === null && $projectId === null) {
            throw ValidationException::withMessages([
                'context' => __('todos.validation.time_entry_context'),
            ]);
        }

        $todo = $todoId === null ? null : $this->findTrackableTodoFor($user, $todoId);
        $project = $projectId === null ? null : $this->findActiveProjectFor($user, $projectId);

        if ($todo instanceof Todo && $todo->project_id !== null) {
            if ($project instanceof Project && $project->id !== $todo->project_id) {
                throw ValidationException::withMessages([
                    'project_id' => __('todos.validation.time_entry_project_mismatch'),
                ]);
            }

            $project = Project::query()
                ->ownedBy($user)
                ->find($todo->project_id);
        }

        return [
            'todo' => $todo,
            'project' => $project,
        ];
    }

    public function parseEntryDate(string $entryDate): Carbon
    {
        try {
            $date = Carbon::createFromFormat('Y-m-d', $entryDate, config('app.timezone'));
        } catch (InvalidFormatException) {
            throw ValidationException::withMessages([
                'entry_date' => __('todos.validation.time_entry_date'),
            ]);
        }

        $date = $date->startOfDay();

        if ($date->format('Y-m-d') !== $entryDate || $date->isAfter(today())) {
            throw ValidationException::withMessages([
                'entry_date' => __('todos.validation.time_entry_date'),
            ]);
        }

        return $date;
    }
}
