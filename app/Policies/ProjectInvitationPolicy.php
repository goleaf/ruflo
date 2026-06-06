<?php

namespace App\Policies;

use App\Models\ProjectInvitation;
use App\Models\User;
use App\Support\Projects\ProjectAccess;
use Illuminate\Auth\Access\Response;

final class ProjectInvitationPolicy
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
    public function view(User $user, ProjectInvitation $projectInvitation): Response
    {
        return app(ProjectAccess::class)->canManageMembers($user, $projectInvitation->project)
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
    public function update(User $user, ProjectInvitation $projectInvitation): Response
    {
        return $this->view($user, $projectInvitation);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProjectInvitation $projectInvitation): Response
    {
        return $this->view($user, $projectInvitation);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProjectInvitation $projectInvitation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProjectInvitation $projectInvitation): bool
    {
        return false;
    }
}
