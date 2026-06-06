<?php

namespace App\Actions\Projects;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AddProjectMember
{
    public function handle(User $actor, Project $project, User $member, ProjectRole $role): ProjectMembership
    {
        Gate::forUser($actor)->authorize('manageMembers', $project);

        if ($member->id === $project->user_id) {
            throw ValidationException::withMessages([
                'member' => __('todos.collaboration.validation.owner_not_member'),
            ]);
        }

        if ($role === ProjectRole::Owner) {
            throw ValidationException::withMessages([
                'role' => __('todos.collaboration.validation.owner_role_reserved'),
            ]);
        }

        return ProjectMembership::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'user_id' => $member->id,
            ],
            [
                'added_by_user_id' => $actor->id,
                'role' => $role,
                'removed_at' => null,
            ],
        );
    }
}
