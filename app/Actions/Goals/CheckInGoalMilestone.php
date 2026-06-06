<?php

namespace App\Actions\Goals;

use App\Models\GoalMilestone;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class CheckInGoalMilestone
{
    public function handle(User $user, GoalMilestone $milestone): GoalMilestone
    {
        Gate::forUser($user)->authorize('update', $milestone);

        $milestone->forceFill([
            'completed_at' => $milestone->isCompleted() ? null : now(),
        ])->save();

        return $milestone->refresh();
    }
}
