<?php

namespace App\Policies;

use App\Models\TodoChecklistItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class TodoChecklistItemPolicy
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
    public function view(User $user, TodoChecklistItem $todoChecklistItem): Response
    {
        return $this->ownerOnly($user, $todoChecklistItem);
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
    public function update(User $user, TodoChecklistItem $todoChecklistItem): Response
    {
        return $this->ownerOnly($user, $todoChecklistItem);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TodoChecklistItem $todoChecklistItem): Response
    {
        return $this->ownerOnly($user, $todoChecklistItem);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TodoChecklistItem $todoChecklistItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TodoChecklistItem $todoChecklistItem): bool
    {
        return false;
    }

    private function ownerOnly(User $user, TodoChecklistItem $todoChecklistItem): Response
    {
        return $todoChecklistItem->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
