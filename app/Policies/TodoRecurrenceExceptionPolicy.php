<?php

namespace App\Policies;

use App\Models\TodoRecurrenceException;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class TodoRecurrenceExceptionPolicy
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
    public function view(User $user, TodoRecurrenceException $todoRecurrenceException): Response
    {
        return $this->ownerOnly($user, $todoRecurrenceException);
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
    public function update(User $user, TodoRecurrenceException $todoRecurrenceException): Response
    {
        return $this->ownerOnly($user, $todoRecurrenceException);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TodoRecurrenceException $todoRecurrenceException): Response
    {
        return $this->ownerOnly($user, $todoRecurrenceException);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TodoRecurrenceException $todoRecurrenceException): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TodoRecurrenceException $todoRecurrenceException): bool
    {
        return false;
    }

    private function ownerOnly(User $user, TodoRecurrenceException $todoRecurrenceException): Response
    {
        return $todoRecurrenceException->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
