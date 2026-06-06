<?php

namespace App\Policies;

use App\Models\TodoDependency;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class TodoDependencyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TodoDependency $todoDependency): Response
    {
        return $this->ownerOnly($user, $todoDependency);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TodoDependency $todoDependency): Response
    {
        return $this->ownerOnly($user, $todoDependency);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TodoDependency $todoDependency): Response
    {
        return $this->ownerOnly($user, $todoDependency);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TodoDependency $todoDependency): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TodoDependency $todoDependency): bool
    {
        return false;
    }

    private function ownerOnly(User $user, TodoDependency $todoDependency): Response
    {
        return $todoDependency->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
