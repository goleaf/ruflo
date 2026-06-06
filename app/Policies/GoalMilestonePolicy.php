<?php

namespace App\Policies;

use App\Models\GoalMilestone;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class GoalMilestonePolicy
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
    public function view(User $user, GoalMilestone $goalMilestone): Response
    {
        return $this->ownerOnly($user, $goalMilestone);
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
    public function update(User $user, GoalMilestone $goalMilestone): Response
    {
        return $this->ownerOnly($user, $goalMilestone);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GoalMilestone $goalMilestone): Response
    {
        return $this->ownerOnly($user, $goalMilestone);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GoalMilestone $goalMilestone): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GoalMilestone $goalMilestone): bool
    {
        return false;
    }

    private function ownerOnly(User $user, GoalMilestone $goalMilestone): Response
    {
        return $goalMilestone->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
