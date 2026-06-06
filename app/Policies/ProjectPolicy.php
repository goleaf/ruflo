<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\Projects\ProjectAccess;
use Illuminate\Auth\Access\Response;

/**
 * Projects are private to their owner unless an active membership grants
 * explicit access. Denials read as "not found" so project existence does not
 * leak across workspaces.
 */
final class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): Response
    {
        return app(ProjectAccess::class)->canView($user, $project)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): Response
    {
        return app(ProjectAccess::class)->canUpdateProject($user, $project)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function archive(User $user, Project $project): Response
    {
        return $this->ownerOnly($user, $project);
    }

    public function restore(User $user, Project $project): Response
    {
        return $this->ownerOnly($user, $project);
    }

    public function delete(User $user, Project $project): Response
    {
        return $this->ownerOnly($user, $project);
    }

    public function manageMembers(User $user, Project $project): Response
    {
        return app(ProjectAccess::class)->canManageMembers($user, $project)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }

    private function ownerOnly(User $user, Project $project): Response
    {
        return $project->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
