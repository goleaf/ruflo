<?php

namespace App\Data\Goals;

use App\Models\Goal;
use App\Models\Todo;

final readonly class GoalProgress
{
    public function __construct(
        public int $completedUnits,
        public int $totalUnits,
        public int $percent,
        public int $completedTasks,
        public int $totalTasks,
        public int $completedMilestones,
        public int $totalMilestones,
    ) {}

    public static function forGoal(Goal $goal): self
    {
        $milestones = $goal->milestones;
        $milestoneTasks = $milestones
            ->flatMap(fn ($milestone) => $milestone->todos)
            ->filter(fn (Todo $todo): bool => $todo->deleted_at === null);
        $directTasks = $goal->todos->filter(fn (Todo $todo): bool => $todo->deleted_at === null);
        $tasks = $directTasks
            ->concat($milestoneTasks)
            ->unique('id')
            ->values();

        $completedTasks = $tasks->filter(fn (Todo $todo): bool => $todo->is_completed)->count();
        $completedMilestones = $milestones->filter->isCompleted()->count();
        $totalTasks = $tasks->count();
        $totalMilestones = $milestones->count();
        $completedUnits = $completedTasks + $completedMilestones;
        $totalUnits = $totalTasks + $totalMilestones;

        return new self(
            completedUnits: $completedUnits,
            totalUnits: $totalUnits,
            percent: $totalUnits === 0 ? 0 : (int) round(($completedUnits / $totalUnits) * 100),
            completedTasks: $completedTasks,
            totalTasks: $totalTasks,
            completedMilestones: $completedMilestones,
            totalMilestones: $totalMilestones,
        );
    }
}
