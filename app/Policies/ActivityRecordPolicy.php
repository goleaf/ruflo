<?php

namespace App\Policies;

use App\Models\ActivityRecord;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class ActivityRecordPolicy
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
    public function view(User $user, ActivityRecord $activityRecord): Response
    {
        return $activityRecord->isOwnedBy($user)
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
    public function update(User $user, ActivityRecord $activityRecord): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ActivityRecord $activityRecord): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ActivityRecord $activityRecord): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ActivityRecord $activityRecord): bool
    {
        return false;
    }
}
