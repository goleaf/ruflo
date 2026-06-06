<?php

namespace App\Policies;

use App\Models\TodoTemplate;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class TodoTemplatePolicy
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
    public function view(User $user, TodoTemplate $todoTemplate): Response
    {
        return $this->ownerOnly($user, $todoTemplate);
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
    public function update(User $user, TodoTemplate $todoTemplate): Response
    {
        return $this->ownerOnly($user, $todoTemplate);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TodoTemplate $todoTemplate): Response
    {
        return $this->ownerOnly($user, $todoTemplate);
    }

    public function instantiate(User $user, TodoTemplate $todoTemplate): Response
    {
        return $this->ownerOnly($user, $todoTemplate);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TodoTemplate $todoTemplate): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TodoTemplate $todoTemplate): bool
    {
        return false;
    }

    private function ownerOnly(User $user, TodoTemplate $todoTemplate): Response
    {
        return $todoTemplate->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
