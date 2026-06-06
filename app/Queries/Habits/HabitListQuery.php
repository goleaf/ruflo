<?php

namespace App\Queries\Habits;

use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class HabitListQuery
{
    /**
     * @return Collection<int, Habit>
     */
    public function for(User $user): Collection
    {
        return $this->base($user)
            ->active()
            ->orderBy('title')
            ->get();
    }

    public function findFor(User $user, int $habitId): Habit
    {
        return $this->base($user)->findOrFail($habitId);
    }

    public function findCheckInFor(User $user, int $checkInId): HabitCheckIn
    {
        return HabitCheckIn::query()
            ->ownedBy($user)
            ->with('habit')
            ->findOrFail($checkInId);
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
            ->select(['id', 'user_id', 'project_id', 'goal_id', 'goal_milestone_id', 'habit_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->with([
                'project' => fn ($query) => $query->whereBelongsTo($user),
                'goal' => fn ($query) => $query->whereBelongsTo($user),
            ])
            ->orderByRaw('is_completed asc')
            ->latest('updated_at')
            ->limit(30)
            ->get();
    }

    /**
     * @return Builder<Habit>
     */
    private function base(User $user): Builder
    {
        return Habit::query()
            ->select(['id', 'user_id', 'goal_id', 'title', 'description', 'frequency', 'target_count', 'starts_on', 'archived_at', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->with([
                'goal' => fn ($query) => $query->whereBelongsTo($user),
                'goal.project' => fn ($query) => $query->whereBelongsTo($user),
                'checkIns' => fn ($query) => $query->whereBelongsTo($user),
                'todos' => fn ($query) => $query
                    ->whereBelongsTo($user)
                    ->whereNull('deleted_at')
                    ->orderByRaw('is_completed asc')
                    ->latest('updated_at'),
            ]);
    }
}
