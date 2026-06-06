<?php

namespace App\Actions\Projects;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class UpdateProjectMemberRole
{
    public function handle(User $actor, Project $project, ProjectMembership $membership, ProjectRole $role): ProjectMembership
    {
        if ($membership->project_id !== $project->id || ! $membership->isActive()) {
            throw (new ModelNotFoundException)->setModel(ProjectMembership::class);
        }

        Gate::forUser($actor)->authorize('update', $membership);

        if ($membership->user_id === $project->user_id) {
            throw ValidationException::withMessages([
                'membership' => __('todos.collaboration.members.validation.owner_not_editable'),
            ]);
        }

        if ($role === ProjectRole::Owner) {
            throw ValidationException::withMessages([
                'memberRole' => __('todos.collaboration.members.validation.role'),
            ]);
        }

        $membership->forceFill([
            'role' => $role,
        ])->save();

        return $membership;
    }
}
