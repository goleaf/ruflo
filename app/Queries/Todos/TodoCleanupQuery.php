<?php

namespace App\Queries\Todos;

use App\Data\Todos\TodoCleanupFilters;
use App\Enums\Priority;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class TodoCleanupQuery
{
    public const int StaleAfterDays = 14;

    /**
     * @return Builder<Todo>
     */
    public function for(User $user, TodoCleanupFilters $filters): Builder
    {
        $query = $this->base($user);

        if ($filters->hasInvalidFilter) {
            $this->rejectInvalidFilter($query);
            $this->applySort($query, $filters->sort, $filters->direction);

            return $query;
        }

        $this->applyView($query, $user, $filters->view);

        if ($filters->search !== null && $filters->search !== '') {
            $query->matching($filters->search);
        }

        $this->applySort($query, $filters->sort, $filters->direction);

        return $query;
    }

    /**
     * @return array{stale: int, unplanned: int, blocked: int, risky: int}
     */
    public function summaryFor(User $user): array
    {
        return [
            TodoCleanupFilters::Stale => $this->forView($user, TodoCleanupFilters::Stale)->count(),
            TodoCleanupFilters::Unplanned => $this->forView($user, TodoCleanupFilters::Unplanned)->count(),
            TodoCleanupFilters::Blocked => $this->forView($user, TodoCleanupFilters::Blocked)->count(),
            TodoCleanupFilters::Risky => $this->forView($user, TodoCleanupFilters::Risky)->count(),
        ];
    }

    /**
     * @return Builder<Todo>
     */
    public function forView(User $user, string $view): Builder
    {
        $query = $this->base($user);
        $this->applyView($query, $user, $view);

        return $query;
    }

    /**
     * @return Builder<Todo>
     */
    private function base(User $user): Builder
    {
        return $this->withWorkspaceRelations(
            Todo::query()
                ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'inbox_captured_at', 'deleted_at', 'created_at', 'updated_at'])
                ->ownedBy($user)
                ->active(),
            $user,
        );
    }

    /**
     * @param  Builder<Todo>  $query
     */
    private function applyView(Builder $query, User $user, string $view): void
    {
        match ($view) {
            TodoCleanupFilters::Stale => $this->whereStale($query),
            TodoCleanupFilters::Unplanned => $this->whereUnplanned($query, $user),
            TodoCleanupFilters::Blocked => $this->whereBlocked($query, $user),
            TodoCleanupFilters::Risky => $this->whereRisky($query, $user),
            default => $this->rejectInvalidFilter($query),
        };
    }

    /**
     * @param  Builder<Todo>  $query
     */
    private function whereStale(Builder $query): void
    {
        $query->where('todos.updated_at', '<=', now()->subDays(self::StaleAfterDays));
    }

    /**
     * @param  Builder<Todo>  $query
     */
    private function whereUnplanned(Builder $query, User $user): void
    {
        $query
            ->whereNull('todos.project_id')
            ->whereNull('todos.due_date')
            ->whereNull('todos.inbox_captured_at')
            ->whereDoesntHave('tags', fn (Builder $tags): Builder => $tags->where('tags.user_id', $user->id));
    }

    /**
     * @param  Builder<Todo>  $query
     */
    private function whereBlocked(Builder $query, User $user): void
    {
        $query->whereHas('dependencies', fn (Builder $dependencies): Builder => $dependencies
            ->where('todo_dependencies.user_id', $user->id)
            ->whereHas('blocker', fn (Builder $blocker): Builder => $blocker
                ->where('todos.user_id', $user->id)
                ->where('todos.is_completed', false)));
    }

    /**
     * @param  Builder<Todo>  $query
     */
    private function whereRisky(Builder $query, User $user): void
    {
        $query->where(function (Builder $risk) use ($user): void {
            $risk
                ->where(function (Builder $urgent): void {
                    $urgent
                        ->where('todos.priority', Priority::Urgent->value)
                        ->where(function (Builder $scope): void {
                            $scope
                                ->whereNull('todos.due_date')
                                ->orWhereDate('todos.due_date', '<=', today());
                        });
                })
                ->orWhere(function (Builder $highOverdue): void {
                    $highOverdue
                        ->where('todos.priority', Priority::High->value)
                        ->whereNotNull('todos.due_date')
                        ->whereDate('todos.due_date', '<', today());
                })
                ->orWhere(function (Builder $blockedOverdue) use ($user): void {
                    $this->whereBlocked($blockedOverdue, $user);
                    $blockedOverdue
                        ->whereNotNull('todos.due_date')
                        ->whereDate('todos.due_date', '<=', today());
                });
        });
    }

    /**
     * @param  Builder<Todo>  $query
     */
    private function applySort(Builder $query, string $sort, string $direction): void
    {
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            TodoCleanupFilters::UpdatedSort => $query
                ->orderBy('todos.updated_at', $direction)
                ->orderByDesc('todos.id'),
            TodoCleanupFilters::DueSort => $query
                ->orderByRaw('todos.due_date is null')
                ->orderBy('todos.due_date', $direction)
                ->orderByRaw(Priority::sortCaseSql().' desc')
                ->orderByDesc('todos.id'),
            TodoCleanupFilters::PrioritySort => $query
                ->orderByRaw(Priority::sortCaseSql().' '.$direction)
                ->orderByRaw('todos.due_date is null')
                ->orderBy('todos.due_date')
                ->orderByDesc('todos.id'),
            TodoCleanupFilters::TitleSort => $query
                ->orderBy('todos.title', $direction)
                ->orderByDesc('todos.id'),
            default => $query
                ->orderByRaw(Priority::sortCaseSql().' desc')
                ->orderByRaw('todos.due_date is null')
                ->orderBy('todos.due_date')
                ->orderBy('todos.updated_at')
                ->orderByDesc('todos.id'),
        };
    }

    /**
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    private function withWorkspaceRelations(Builder $query, User $user): Builder
    {
        return $query->with([
            'project' => fn (BelongsTo $project): BelongsTo => $project->where('projects.user_id', $user->id),
            'tags' => fn (BelongsToMany $tags): BelongsToMany => $tags->where('tags.user_id', $user->id),
            'dependencies.blocker' => fn (BelongsTo $blocker): BelongsTo => $blocker->where('todos.user_id', $user->id),
        ])->withCount([
            'dependencies as open_dependencies_count' => fn (Builder $dependencies): Builder => $dependencies
                ->whereHas('blocker', fn (Builder $blocker): Builder => $blocker
                    ->where('todos.user_id', $user->id)
                    ->where('todos.is_completed', false)),
        ]);
    }

    /**
     * @param  Builder<Todo>  $query
     */
    private function rejectInvalidFilter(Builder $query): void
    {
        $query->whereKey([]);
    }
}
