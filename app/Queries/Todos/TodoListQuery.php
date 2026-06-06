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
 * The single owner-scoped read boundary for todos.
 *
 * Every todo list, lookup, filter, sort, and counter must originate here so
 * that no query can observe another user's private data. Ownership is applied
 * through {@see Todo::scopeOwnedBy()}; lifecycle bucketing and organization
 * filters are applied through the model's scopes.
 */
final class TodoListQuery
{
    /**
     * Base owner-scoped query for the user's visible (non-deleted) todos.
     *
     * @return Builder<Todo>
     */
    public function visibleFor(User $user): Builder
    {
        return Todo::query()
            ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->latest();
    }

    /**
     * Owner-scoped query narrowed to a single lifecycle bucket.
     *
     * @return Builder<Todo>
     */
    public function forStatus(User $user, TodoStatus $status): Builder
    {
        return $this->applyStatus($this->visibleFor($user), $status);
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
            Todo::query()
                ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
                ->ownedBy($user),
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
            if ($this->ownedActiveProjectExists($user, $filters->projectId)) {
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
            'blocked' => $this->whereBlocked($query, $user),
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
            Todo::query()
                ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
                ->ownedBy($user)
                ->dueToday(),
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
            Todo::query()
                ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
                ->ownedBy($user)
                ->overdue(),
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
            Todo::query()
                ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
                ->ownedBy($user)
                ->upcoming(),
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
            Todo::query()
                ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
                ->ownedBy($user)
                ->active(),
            $user,
        );

        $this->whereBlocked($query, $user);

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
            ->ownedBy($user)
            ->selectRaw('sum(case when deleted_at is null and archived_at is null and is_completed = 0 then 1 else 0 end) as active_count')
            ->selectRaw('sum(case when deleted_at is null and archived_at is null and is_completed = 1 then 1 else 0 end) as completed_count')
            ->selectRaw('sum(case when deleted_at is null and archived_at is not null then 1 else 0 end) as archived_count')
            ->selectRaw('sum(case when deleted_at is not null then 1 else 0 end) as trash_count')
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
        return $this->withWorkspaceRelationsForOwnerId($query, $user->id);
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
    private function whereBlocked(Builder $query, User $user): Builder
    {
        return $query->whereHas('dependencies', fn (Builder $dependencies): Builder => $dependencies
            ->where('todo_dependencies.user_id', $user->id)
            ->whereHas('blocker', fn (Builder $blocker): Builder => $blocker
                ->where('todos.user_id', $user->id)
                ->where('todos.is_completed', false)));
    }

    private function ownedActiveProjectExists(User $user, int $projectId): bool
    {
        return Project::query()
            ->ownedBy($user)
            ->active()
            ->whereKey($projectId)
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
}
