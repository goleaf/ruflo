<?php

namespace App\Policies;

use App\Models\SavedTodoView;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class SavedTodoViewPolicy
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
    public function view(User $user, SavedTodoView $savedTodoView): Response
    {
        return $this->ownerOnly($user, $savedTodoView);
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
    public function update(User $user, SavedTodoView $savedTodoView): Response
    {
        return $this->ownerOnly($user, $savedTodoView);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SavedTodoView $savedTodoView): Response
    {
        return $this->ownerOnly($user, $savedTodoView);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SavedTodoView $savedTodoView): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SavedTodoView $savedTodoView): bool
    {
        return false;
    }

    private function ownerOnly(User $user, SavedTodoView $savedTodoView): Response
    {
        return $savedTodoView->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
