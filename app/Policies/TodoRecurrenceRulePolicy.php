<?php

namespace App\Policies;

use App\Models\TodoRecurrenceRule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class TodoRecurrenceRulePolicy
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
    public function view(User $user, TodoRecurrenceRule $todoRecurrenceRule): Response
    {
        return $this->ownerOnly($user, $todoRecurrenceRule);
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
    public function update(User $user, TodoRecurrenceRule $todoRecurrenceRule): Response
    {
        return $this->ownerOnly($user, $todoRecurrenceRule);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TodoRecurrenceRule $todoRecurrenceRule): Response
    {
        return $this->ownerOnly($user, $todoRecurrenceRule);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TodoRecurrenceRule $todoRecurrenceRule): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TodoRecurrenceRule $todoRecurrenceRule): bool
    {
        return false;
    }

    private function ownerOnly(User $user, TodoRecurrenceRule $todoRecurrenceRule): Response
    {
        return $todoRecurrenceRule->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
