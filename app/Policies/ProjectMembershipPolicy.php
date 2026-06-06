<?php

namespace App\Policies;

use App\Models\ProjectMembership;
use App\Models\User;
use App\Support\Projects\ProjectAccess;
use Illuminate\Auth\Access\Response;

final class ProjectMembershipPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProjectMembership $projectMembership): Response
    {
        return app(ProjectAccess::class)->canView($user, $projectMembership->project)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProjectMembership $projectMembership): Response
    {
        return $this->manageable($user, $projectMembership);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProjectMembership $projectMembership): Response
    {
        return $this->manageable($user, $projectMembership);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProjectMembership $projectMembership): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProjectMembership $projectMembership): bool
    {
        return false;
    }

    private function manageable(User $user, ProjectMembership $projectMembership): Response
    {
        if (! app(ProjectAccess::class)->canManageMembers($user, $projectMembership->project)) {
            return Response::denyAsNotFound();
        }

        if ($projectMembership->project->user_id === $projectMembership->user_id) {
            return Response::deny(__('todos.collaboration.validation.owner_not_removable'));
        }

        return Response::allow();
    }
}
