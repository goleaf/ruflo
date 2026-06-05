<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Projects are private to their owner. Denials read as "not found" so a
 * project's existence never leaks across workspaces.
 */
final class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): Response
    {
        return $this->ownerOnly($user, $project);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): Response
    {
        return $this->ownerOnly($user, $project);
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

    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }

    private function ownerOnly(User $user, Project $project): Response
    {
        return $project->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
