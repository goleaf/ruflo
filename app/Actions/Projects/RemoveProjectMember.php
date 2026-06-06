<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Queries\Projects\ProjectMembershipQuery;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class RemoveProjectMember
{
    public function handle(User $actor, Project $project, User $member): ProjectMembership
    {
        Gate::forUser($actor)->authorize('manageMembers', $project);

        if ($member->id === $project->user_id) {
            throw ValidationException::withMessages([
                'member' => __('todos.collaboration.validation.owner_not_removable'),
            ]);
        }

        $membership = app(ProjectMembershipQuery::class)->activeForMember($project, $member);

        if (! $membership instanceof ProjectMembership) {
            throw (new ModelNotFoundException)->setModel(ProjectMembership::class);
        }

        $membership->forceFill([
            'removed_at' => now(),
        ])->save();

        return $membership;
    }
}
