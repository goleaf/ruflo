<?php

namespace App\Actions\Habits;

use App\Data\Habits\HabitData;
use App\Enums\HabitFrequency;
use App\Models\Goal;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CreateHabit
{
    public function handle(User $user, HabitData $data): Habit
    {
        Gate::forUser($user)->authorize('create', Habit::class);
        $this->ensureGoalBelongsToUser($user, $data);
        $this->ensureTargetCountMatchesFrequency($data);

        $habit = $user->habits()->make([
            'title' => $data->title,
            'description' => $data->description,
            'frequency' => $data->frequency,
            'target_count' => $data->targetCount,
            'starts_on' => today()->toDateString(),
        ]);

        $habit->forceFill([
            'goal_id' => $data->goalId,
        ])->save();

        return $habit;
    }

    private function ensureGoalBelongsToUser(User $user, HabitData $data): void
    {
        if ($data->goalId === null) {
            return;
        }

        $exists = Goal::query()
            ->ownedBy($user)
            ->active()
            ->whereKey($data->goalId)
            ->exists();

        if ($exists) {
            return;
        }

        throw ValidationException::withMessages([
            'goal_id' => __('habits.validation.goal_required'),
        ]);
    }

    private function ensureTargetCountMatchesFrequency(HabitData $data): void
    {
        $valid = match ($data->frequency) {
            HabitFrequency::Daily => $data->targetCount === 1,
            HabitFrequency::Weekly => $data->targetCount >= 1 && $data->targetCount <= 7,
        };

        if ($valid) {
            return;
        }

        throw ValidationException::withMessages([
            'target_count' => $data->frequency === HabitFrequency::Daily
                ? __('habits.validation.target_daily')
                : __('habits.validation.target_count'),
        ]);
    }
}
