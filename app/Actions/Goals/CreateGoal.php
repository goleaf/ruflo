<?php

namespace App\Actions\Goals;

use App\Data\Goals\GoalData;
use App\Models\Goal;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CreateGoal
{
    public function handle(User $user, GoalData $data): Goal
    {
        Gate::forUser($user)->authorize('create', Goal::class);
        $this->ensureProjectBelongsToUser($user, $data);

        $goal = $user->goals()->make([
            'title' => $data->title,
            'description' => $data->description,
            'target_date' => $data->targetDate,
        ]);

        $goal->forceFill([
            'project_id' => $data->projectId,
        ])->save();

        return $goal;
    }

    private function ensureProjectBelongsToUser(User $user, GoalData $data): void
    {
        if ($data->projectId === null) {
            return;
        }

        $exists = Project::query()
            ->ownedBy($user)
            ->active()
            ->whereKey($data->projectId)
            ->exists();

        if ($exists) {
            return;
        }

        throw ValidationException::withMessages([
            'project_id' => __('todos.validation.owned_active_project'),
        ]);
    }
}
