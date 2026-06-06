<?php

namespace App\Actions\Goals;

use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class LinkTodoToGoal
{
    public function handle(User $user, Goal $goal, Todo $todo, ?GoalMilestone $milestone = null): Todo
    {
        Gate::forUser($user)->authorize('update', $goal);
        Gate::forUser($user)->authorize('update', $todo);

        if ($milestone !== null) {
            Gate::forUser($user)->authorize('update', $milestone);
            $this->assertMilestoneBelongsToGoal($goal, $milestone);
        }

        $this->assertTodoCanBeLinked($todo);

        $todo->forceFill([
            'goal_id' => $goal->id,
            'goal_milestone_id' => $milestone?->id,
        ])->save();

        return $todo->refresh();
    }

    private function assertMilestoneBelongsToGoal(Goal $goal, GoalMilestone $milestone): void
    {
        if ($milestone->goal_id === $goal->id) {
            return;
        }

        throw ValidationException::withMessages([
            'milestone_id' => __('goals.validation.milestone_goal'),
        ]);
    }

    private function assertTodoCanBeLinked(Todo $todo): void
    {
        if ($todo->deleted_at === null && ! $todo->isArchived()) {
            return;
        }

        throw ValidationException::withMessages([
            'todo_id' => __('goals.validation.linkable_todo'),
        ]);
    }
}
