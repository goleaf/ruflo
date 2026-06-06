<?php

namespace App\Queries\Todos;

use App\Enums\Priority;
use App\Enums\TodoStatus;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * The single read boundary for private todos plus active shared project todos.
 *
 * Every todo list, lookup, filter, sort, and counter must originate here so
 * that no query can observe another user's private data. Private ownership and
 * active project membership are applied before lifecycle buckets, filters, and
 * sorting.
 */
final class TodoListQuery
{
    /**
     * Base read-scoped query for the user's visible (non-deleted) todos.
     *
     * @return Builder<Todo>
     */
    public function visibleFor(User $user): Builder
    {
        return $this->accessibleSelect($user)
            ->latest();
    }

    /**
     * Owner-scoped query narrowed to a single lifecycle bucket.
     *
     * @return Builder<Todo>
     */
    public function forStatus(User $user, TodoStatus $status): Builder
    {
        $query = $this->accessibleSelect($user, includeDeletedOwnerRows: $status === TodoStatus::Trash);

        $this->applyStatus($query, $status);

        return $query;
    }

    /**
     * Owner-scoped, fully filtered + sorted query for the task list.
     *
     * Eager loads project and tags to keep row rendering free of N+1 queries.
     *
     * @return Builder<Todo>
     */
    public function filtered(User $user, TodoFilters $filters): Builder
    {
        $query = $this->withWorkspaceRelations(
            $this->accessibleSelect($user, includeDeletedOwnerRows: $filters->status === TodoStatus::Trash),
            $user,
        );

        $this->applyStatus($query, $filters->status);

        if ($filters->hasInvalidFilter) {
            $this->rejectInvalidFilter($query);
            $this->applySort($query, $filters->sort, $filters->direction);

            return $query;
        }

        if ($filters->search !== null && $filters->search !== '') {
            $query->matching($filters->search);
        }

        if ($filters->withoutProject) {
            $query->withoutProject();
        } elseif ($filters->projectId !== null) {
            if ($this->activeAccessibleProjectExists($user, $filters->projectId)) {
                $query->forProject($filters->projectId);
            } else {
                $this->rejectInvalidFilter($query);
            }
        }

        if ($filters->tagId !== null) {
            if ($this->ownedTagExists($user, $filters->tagId)) {
                $query->withTag($filters->tagId);
            } else {
                $this->rejectInvalidFilter($query);
            }
        }

        if ($filters->priority !== null) {
            $query->withPriority($filters->priority);
        }

        match ($filters->due) {
            'today' => $query->dueToday(),
            'overdue' => $query->overdue(),
            'upcoming' => $query->upcoming(),
            'blocked' => $this->whereBlocked($query),
            'with' => $query->whereNotNull('due_date'),
            'without' => $query->whereNull('due_date'),
            default => null,
        };

        $this->applySort($query, $filters->sort, $filters->direction);

        return $query;
    }

    /**
     * Owner-scoped tasks assigned to one visible project detail page.
     *
     * Archived projects can still show their existing tasks here; the active
     * project restriction only applies to picker/filter assignment surfaces.
     *
     * @return Builder<Todo>
     */
    public function forProjectDetail(User $user, Project $project): Builder
    {
        return $this->withWorkspaceRelations(
            Todo::query()
                ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
                ->where('user_id', $project->user_id)
                ->where('project_id', $project->id),
            $this->workspaceOwnerFor($project),
        )->latest();
    }

    /**
     * Owner-scoped active tasks due today.
     *
     * @return Builder<Todo>
     */
    public function todayFor(User $user): Builder
    {
        return $this->withWorkspaceRelations(
            $this->accessibleSelect($user)->dueToday(),
            $user,
        )
            ->orderByRaw(Priority::sortCaseSql().' desc')
            ->orderByDesc('created_at');
    }

    /**
     * Resolve one due-today task for a focused Today action.
     */
    public function findTodayFor(User $user, int $todoId): Todo
    {
        return $this->todayFor($user)->findOrFail($todoId);
    }

    /**
     * Owner-scoped active tasks past their due date.
     *
     * @return Builder<Todo>
     */
    public function overdueFor(User $user): Builder
    {
        return $this->withWorkspaceRelations(
            $this->accessibleSelect($user)->overdue(),
            $user,
        )
            ->orderBy('due_date')
            ->orderByRaw(Priority::sortCaseSql().' desc')
            ->orderByDesc('created_at');
    }

    /**
     * Resolve one overdue task for a focused Overdue action.
     */
    public function findOverdueFor(User $user, int $todoId): Todo
    {
        return $this->overdueFor($user)->findOrFail($todoId);
    }

    /**
     * Owner-scoped active tasks due after today.
     *
     * @return Builder<Todo>
     */
    public function upcomingFor(User $user): Builder
    {
        return $this->withWorkspaceRelations(
            $this->accessibleSelect($user)->upcoming(),
            $user,
        )
            ->orderBy('due_date')
            ->orderByRaw(Priority::sortCaseSql().' desc')
            ->orderByDesc('created_at');
    }

    /**
     * Resolve one upcoming task for a focused Upcoming action.
     */
    public function findUpcomingFor(User $user, int $todoId): Todo
    {
        return $this->upcomingFor($user)->findOrFail($todoId);
    }

    /**
     * Owner-scoped active tasks that are waiting on at least one open blocker.
     *
     * @return Builder<Todo>
     */
    public function blockedFor(User $user): Builder
    {
        $query = $this->withWorkspaceRelations(
            $this->accessibleSelect($user)->active(),
            $user,
        );

        $this->whereBlocked($query);

        return $query
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * Resolve one blocked task for the blocked smart view.
     */
    public function findBlockedFor(User $user, int $todoId): Todo
    {
        return $this->blockedFor($user)->findOrFail($todoId);
    }

    /**
     * Resolve a single todo the user is allowed to see.
     *
     * Foreign or unknown ids yield not-found rather than leaking existence.
     */
    public function findVisibleFor(User $user, int $todoId): Todo
    {
        $todo = Todo::query()
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->ownedBy($user)
                    ->orWhereExists(function ($memberships) use ($user): void {
                        $memberships
                            ->selectRaw('1')
                            ->from('project_memberships')
                            ->join('projects', 'projects.id', '=', 'project_memberships.project_id')
                            ->where('project_memberships.user_id', $user->id)
                            ->whereNull('project_memberships.removed_at')
                            ->whereColumn('project_memberships.project_id', 'todos.project_id')
                            ->whereColumn('projects.user_id', 'todos.user_id');
                    });
            })
            ->findOrFail($todoId);

        return $this->loadWorkspaceRelationsFor($todo);
    }

    /**
     * Resolve a single trashed todo the user is allowed to restore.
     *
     * Foreign, active, archived, completed, or unknown ids yield not-found.
     */
    public function findTrashedFor(User $user, int $todoId): Todo
    {
        return $this->withWorkspaceRelations(
            Todo::query()->onlyTrashed()->ownedBy($user),
            $user,
        )->findOrFail($todoId);
    }

    /**
     * Aggregate lifecycle counts for the user's workspace in one scoped query.
     *
     * @return array{active: int, completed: int, archived: int, trash: int, overdue: int, blocked: int}
     */
    public function summaryFor(User $user): array
    {
        $summary = Todo::query()
            ->withTrashed()
            ->where(fn (Builder $query): Builder => $this->whereAccessibleTasks($query, $user, includeDeletedOwnerRows: true))
            ->selectRaw('sum(case when deleted_at is null and archived_at is null and is_completed = 0 then 1 else 0 end) as active_count')
            ->selectRaw('sum(case when deleted_at is null and archived_at is null and is_completed = 1 then 1 else 0 end) as completed_count')
            ->selectRaw('sum(case when deleted_at is null and archived_at is not null then 1 else 0 end) as archived_count')
            ->selectRaw('sum(case when user_id = ? and deleted_at is not null then 1 else 0 end) as trash_count', [$user->id])
            ->selectRaw('sum(case when deleted_at is null and archived_at is null and is_completed = 0 and due_date is not null and due_date < ? then 1 else 0 end) as overdue_count', [today()->toDateString()])
            ->first();

        return [
            'active' => (int) $summary->active_count,
            'completed' => (int) $summary->completed_count,
            'archived' => (int) $summary->archived_count,
            'trash' => (int) $summary->trash_count,
            'overdue' => (int) $summary->overdue_count,
            'blocked' => $this->blockedFor($user)->count(),
        ];
    }

    /**
     * Task-derived dashboard counters across owned tasks and active shared
     * project tasks. Non-task domains such as reminders, time, and
     * notifications remain scoped to the authenticated user elsewhere.
     *
     * @return array{
     *     active: int,
     *     scheduled: int,
     *     today: int,
     *     overdue: int,
     *     due_soon: int,
     *     unplanned: int,
     *     priority_urgent: int,
     *     priority_high: int,
     *     priority_normal: int,
     *     priority_low: int,
     *     projects_with_active_tasks: int,
     *     recurrence_generated: int
     * }
     */
    public function dashboardTaskCountsFor(User $user): array
    {
        $today = today()->toDateString();
        $soonEndsOn = today()->addDays(7)->toDateString();

        $tasks = $this->accessibleAggregate($user)
            ->active()
            ->selectRaw('count(*) as active_count')
            ->selectRaw('sum(case when due_date is not null then 1 else 0 end) as scheduled_count')
            ->selectRaw('sum(case when date(due_date) = ? then 1 else 0 end) as today_count', [$today])
            ->selectRaw('sum(case when due_date is not null and date(due_date) < ? then 1 else 0 end) as overdue_count', [$today])
            ->selectRaw('sum(case when date(due_date) > ? and date(due_date) <= ? then 1 else 0 end) as due_soon_count', [$today, $soonEndsOn])
            ->selectRaw('sum(case when due_date is null then 1 else 0 end) as unplanned_count')
            ->selectRaw('sum(case when priority = ? then 1 else 0 end) as urgent_count', [Priority::Urgent->value])
            ->selectRaw('sum(case when priority = ? then 1 else 0 end) as high_count', [Priority::High->value])
            ->selectRaw('sum(case when priority = ? then 1 else 0 end) as normal_count', [Priority::Normal->value])
            ->selectRaw('sum(case when priority = ? then 1 else 0 end) as low_count', [Priority::Low->value])
            ->first();

        return [
            'active' => (int) ($tasks->active_count ?? 0),
            'scheduled' => (int) ($tasks->scheduled_count ?? 0),
            'today' => (int) ($tasks->today_count ?? 0),
            'overdue' => (int) ($tasks->overdue_count ?? 0),
            'due_soon' => (int) ($tasks->due_soon_count ?? 0),
            'unplanned' => (int) ($tasks->unplanned_count ?? 0),
            'priority_urgent' => (int) ($tasks->urgent_count ?? 0),
            'priority_high' => (int) ($tasks->high_count ?? 0),
            'priority_normal' => (int) ($tasks->normal_count ?? 0),
            'priority_low' => (int) ($tasks->low_count ?? 0),
            'projects_with_active_tasks' => $this->accessibleAggregate($user)
                ->active()
                ->whereNotNull('project_id')
                ->whereExists(function ($projects): void {
                    $projects
                        ->selectRaw('1')
                        ->from('projects')
                        ->whereColumn('projects.id', 'todos.project_id')
                        ->whereColumn('projects.user_id', 'todos.user_id')
                        ->whereNull('projects.archived_at');
                })
                ->distinct('project_id')
                ->count('project_id'),
            'recurrence_generated' => $this->accessibleAggregate($user)
                ->active()
                ->whereNotNull('recurrence_rule_id')
                ->whereNotNull('recurrence_occurs_on')
                ->count(),
        ];
    }

    /**
     * Aggregate lifecycle counts for tasks assigned to a private project.
     *
     * @return array{active: int, completed: int, archived: int, trash: int}
     */
    public function projectSummaryFor(User $user, Project $project): array
    {
        $summary = Todo::query()
            ->withTrashed()
            ->where('user_id', $project->user_id)
            ->where('project_id', $project->id)
            ->selectRaw('sum(case when deleted_at is null and archived_at is null and is_completed = 0 then 1 else 0 end) as active_count')
            ->selectRaw('sum(case when deleted_at is null and archived_at is null and is_completed = 1 then 1 else 0 end) as completed_count')
            ->selectRaw('sum(case when deleted_at is null and archived_at is not null then 1 else 0 end) as archived_count')
            ->selectRaw('sum(case when deleted_at is not null then 1 else 0 end) as trash_count')
            ->first();

        return [
            'active' => (int) $summary->active_count,
            'completed' => (int) $summary->completed_count,
            'archived' => (int) $summary->archived_count,
            'trash' => $project->isOwnedBy($user) ? (int) $summary->trash_count : 0,
        ];
    }

    /**
     * @param  Builder<Todo>  $query
     */
    private function applyStatus(Builder $query, TodoStatus $status): void
    {
        match ($status) {
            TodoStatus::Active => $query->active(),
            TodoStatus::Completed => $query->completed(),
            TodoStatus::Archived => $query->archived(),
            TodoStatus::Trash => $query->onlyTrashed(),
        };
    }

    /**
     * Apply a validated sort. The sort key is constrained by the caller, so
     * the column/expression is never raw user input.
     *
     * @param  Builder<Todo>  $query
     */
    private function applySort(Builder $query, string $sort, string $direction): void
    {
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'due' => $query
                ->orderByRaw('due_date is null')
                ->orderBy('due_date', $direction)
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
            'priority' => $query
                ->orderByRaw(Priority::sortCaseSql().' '.$direction)
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
            'project' => $query
                ->orderByRaw('project_id is null')
                ->orderBy(
                    Project::query()
                        ->select('name')
                        ->whereColumn('projects.id', 'todos.project_id')
                        ->whereColumn('projects.user_id', 'todos.user_id')
                        ->limit(1),
                    $direction,
                )
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
            'title' => $query
                ->orderBy('title', $direction)
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
            'updated' => $query
                ->orderBy('updated_at', $direction)
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
            default => $query
                ->orderBy('created_at', $direction)
                ->orderBy('id', $direction),
        };
    }

    /**
     * Constrain related labels to the same private workspace before rendering.
     *
     * Normal actions already prevent cross-user project/tag links. This extra
     * query-level guard means malformed legacy rows or manual database edits do
     * not leak another user's project or tag names into the UI.
     *
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    private function withWorkspaceRelations(Builder $query, User $user): Builder
    {
        return $query->with([
            'project' => fn (BelongsTo $project): BelongsTo => $project->where(function (Builder $projectQuery) use ($user): void {
                $projectQuery
                    ->where('projects.user_id', $user->id)
                    ->orWhereHas('memberships', fn (Builder $memberships): Builder => $memberships
                        ->active()
                        ->where('user_id', $user->id));
            }),
            'tags' => fn (BelongsToMany $tags): BelongsToMany => $tags->where('tags.user_id', $user->id),
            'dependencies.blocker' => fn ($blocker) => $blocker->whereColumn('todos.user_id', 'todo_dependencies.user_id'),
        ])->withCount([
            'dependencies as open_dependencies_count' => fn (Builder $dependencies): Builder => $dependencies
                ->whereColumn('todo_dependencies.user_id', 'todos.user_id')
                ->whereHas('blocker', fn (Builder $blocker): Builder => $blocker
                    ->whereColumn('todos.user_id', 'todo_dependencies.user_id')
                    ->where('todos.is_completed', false)),
        ]);
    }

    /**
     * Constrain related labels to the workspace owner for owner and shared
     * project reads. Shared members should see the project owner's labels for
     * that project, never labels from their own private workspace.
     *
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    private function withWorkspaceRelationsForOwnerId(Builder $query, int $ownerId): Builder
    {
        return $query->with([
            'project' => fn (BelongsTo $project): BelongsTo => $project->where('projects.user_id', $ownerId),
            'tags' => fn (BelongsToMany $tags): BelongsToMany => $tags->where('tags.user_id', $ownerId),
            'dependencies.blocker' => fn ($blocker) => $blocker->where('todos.user_id', $ownerId),
        ])->withCount([
            'dependencies as open_dependencies_count' => fn (Builder $dependencies): Builder => $dependencies
                ->whereHas('blocker', fn (Builder $blocker): Builder => $blocker
                    ->where('todos.user_id', $ownerId)
                    ->where('todos.is_completed', false)),
        ]);
    }

    private function loadWorkspaceRelationsFor(Todo $todo): Todo
    {
        $ownerId = (int) $todo->user_id;

        $todo->load([
            'project' => fn (BelongsTo $project): BelongsTo => $project->where('projects.user_id', $ownerId),
            'tags' => fn (BelongsToMany $tags): BelongsToMany => $tags->where('tags.user_id', $ownerId),
            'dependencies.blocker' => fn ($blocker) => $blocker->where('todos.user_id', $ownerId),
        ]);

        $todo->loadCount([
            'dependencies as open_dependencies_count' => fn (Builder $dependencies): Builder => $dependencies
                ->whereHas('blocker', fn (Builder $blocker): Builder => $blocker
                    ->where('todos.user_id', $ownerId)
                    ->where('todos.is_completed', false)),
        ]);

        return $todo;
    }

    private function workspaceOwnerFor(Project $project): User
    {
        if ($project->relationLoaded('user') && $project->user instanceof User) {
            return $project->user;
        }

        return User::query()->findOrFail($project->user_id);
    }

    /**
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    private function whereBlocked(Builder $query): Builder
    {
        return $query->whereHas('dependencies', fn (Builder $dependencies): Builder => $dependencies
            ->whereColumn('todo_dependencies.user_id', 'todos.user_id')
            ->whereHas('blocker', fn (Builder $blocker): Builder => $blocker
                ->whereColumn('todos.user_id', 'todo_dependencies.user_id')
                ->where('todos.is_completed', false)));
    }

    private function activeAccessibleProjectExists(User $user, int $projectId): bool
    {
        return Project::query()
            ->whereKey($projectId)
            ->active()
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->ownedBy($user)
                    ->orWhereHas('memberships', fn (Builder $memberships): Builder => $memberships
                        ->active()
                        ->where('user_id', $user->id));
            })
            ->exists();
    }

    private function ownedTagExists(User $user, int $tagId): bool
    {
        return $user->tags()
            ->whereKey($tagId)
            ->exists();
    }

    /**
     * Keep tampered numeric filters from falling back to an unfiltered list.
     *
     * @param  Builder<Todo>  $query
     */
    private function rejectInvalidFilter(Builder $query): void
    {
        $query->whereKey([]);
    }

    /**
     * Base task query for owner rows plus rows from active shared projects.
     *
     * Shared rows are never allowed to include trash, no-project tasks, removed
     * memberships, archived shared projects, or malformed project ownership.
     *
     * @return Builder<Todo>
     */
    private function accessibleSelect(User $user, bool $includeDeletedOwnerRows = false): Builder
    {
        return Todo::query()
            ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
            ->where(fn (Builder $query): Builder => $this->whereAccessibleTasks($query, $user, $includeDeletedOwnerRows));
    }

    /**
     * @return Builder<Todo>
     */
    private function accessibleAggregate(User $user): Builder
    {
        return Todo::query()
            ->where(fn (Builder $query): Builder => $this->whereAccessibleTasks($query, $user));
    }

    /**
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    private function whereAccessibleTasks(Builder $query, User $user, bool $includeDeletedOwnerRows = false): Builder
    {
        return $query
            ->where(function (Builder $owned) use ($user, $includeDeletedOwnerRows): Builder {
                $owned
                    ->where('todos.user_id', $user->id)
                    ->where(function (Builder $projectIntegrity): void {
                        $projectIntegrity
                            ->whereNull('todos.project_id')
                            ->orWhereExists(function ($projects): void {
                                $projects
                                    ->selectRaw('1')
                                    ->from('projects')
                                    ->whereColumn('projects.id', 'todos.project_id')
                                    ->whereColumn('projects.user_id', 'todos.user_id');
                            });
                    });

                if (! $includeDeletedOwnerRows) {
                    $owned->whereNull('todos.deleted_at');
                }

                return $owned;
            })
            ->orWhere(function (Builder $shared) use ($user): Builder {
                return $shared
                    ->whereNull('todos.deleted_at')
                    ->whereNotNull('todos.project_id')
                    ->whereExists(function ($memberships) use ($user): void {
                        $memberships
                            ->selectRaw('1')
                            ->from('project_memberships')
                            ->join('projects', 'projects.id', '=', 'project_memberships.project_id')
                            ->where('project_memberships.user_id', $user->id)
                            ->whereNull('project_memberships.removed_at')
                            ->whereNull('projects.archived_at')
                            ->whereColumn('project_memberships.project_id', 'todos.project_id')
                            ->whereColumn('projects.id', 'todos.project_id')
                            ->whereColumn('projects.user_id', 'todos.user_id');
                    });
            });
    }
}
