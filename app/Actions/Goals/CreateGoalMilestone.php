<?php

namespace App\Actions\Goals;

use App\Data\Goals\GoalMilestoneData;
use App\Models\GoalMilestone;
use App\Models\User;
use App\Queries\Goals\GoalListQuery;
use Illuminate\Support\Facades\Gate;

final class CreateGoalMilestone
{
    public function __construct(
        private readonly GoalListQuery $goals,
    ) {}

    public function handle(User $user, GoalMilestoneData $data): GoalMilestone
    {
        $goal = $this->goals->findFor($user, $data->goalId);

        Gate::forUser($user)->authorize('update', $goal);
        Gate::forUser($user)->authorize('create', GoalMilestone::class);

        $milestone = $goal->milestones()->make([
            'title' => $data->title,
            'target_date' => $data->targetDate,
            'position' => $this->nextPosition($goal->id),
        ]);

        $milestone->user()->associate($user);
        $milestone->save();

        return $milestone;
    }

    private function nextPosition(int $goalId): int
    {
        return ((int) GoalMilestone::query()->where('goal_id', $goalId)->max('position')) + 1;
    }
}
