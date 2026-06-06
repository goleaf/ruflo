<?php

namespace App\Queries\Goals;

use App\Data\Goals\GoalProgress;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class GoalListQuery
{
    /**
     * @return Collection<int, Goal>
     */
    public function for(User $user): Collection
    {
        return $this->base($user)
            ->active()
            ->orderByRaw('completed_at is not null')
            ->orderByRaw('target_date is null')
            ->orderBy('target_date')
            ->latest('updated_at')
            ->get();
    }

    public function findFor(User $user, int $goalId): Goal
    {
        return $this->base($user)->findOrFail($goalId);
    }

    public function findMilestoneFor(User $user, int $milestoneId): GoalMilestone
    {
        return GoalMilestone::query()
            ->ownedBy($user)
            ->with('goal')
            ->findOrFail($milestoneId);
    }

    public function findTodoFor(User $user, int $todoId): Todo
    {
        return Todo::query()
            ->ownedBy($user)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->findOrFail($todoId);
    }

    /**
     * @return Collection<int, Todo>
     */
    public function availableTodosFor(User $user): Collection
    {
        return Todo::query()
            ->select(['id', 'user_id', 'project_id', 'goal_id', 'goal_milestone_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->with([
                'project' => fn ($query) => $query->whereBelongsTo($user),
            ])
            ->orderByRaw('is_completed asc')
            ->latest('updated_at')
            ->limit(30)
            ->get();
    }

    /**
     * @return array<int, GoalProgress>
     */
    public function progressMapFor(User $user): array
    {
        return $this->for($user)
            ->mapWithKeys(fn (Goal $goal): array => [$goal->id => GoalProgress::forGoal($goal)])
            ->all();
    }

    /**
     * @return Builder<Goal>
     */
    private function base(User $user): Builder
    {
        return Goal::query()
            ->select(['id', 'user_id', 'project_id', 'title', 'description', 'target_date', 'completed_at', 'archived_at', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->with([
                'project' => fn ($query) => $query->whereBelongsTo($user),
                'todos' => fn ($query) => $query
                    ->whereBelongsTo($user)
                    ->whereNull('deleted_at')
                    ->orderByRaw('is_completed asc')
                    ->latest('updated_at'),
                'milestones' => fn ($query) => $query->whereBelongsTo($user),
                'milestones.todos' => fn ($query) => $query
                    ->whereBelongsTo($user)
                    ->whereNull('deleted_at')
                    ->orderByRaw('is_completed asc')
                    ->latest('updated_at'),
            ]);
    }
}
