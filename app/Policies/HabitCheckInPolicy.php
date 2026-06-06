<?php

namespace App\Policies;

use App\Models\HabitCheckIn;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class HabitCheckInPolicy
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
    public function view(User $user, HabitCheckIn $habitCheckIn): Response
    {
        return $this->ownerOnly($user, $habitCheckIn);
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
    public function update(User $user, HabitCheckIn $habitCheckIn): Response
    {
        return $this->ownerOnly($user, $habitCheckIn);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, HabitCheckIn $habitCheckIn): Response
    {
        return $this->ownerOnly($user, $habitCheckIn);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, HabitCheckIn $habitCheckIn): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, HabitCheckIn $habitCheckIn): bool
    {
        return false;
    }

    private function ownerOnly(User $user, HabitCheckIn $habitCheckIn): Response
    {
        return $habitCheckIn->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
