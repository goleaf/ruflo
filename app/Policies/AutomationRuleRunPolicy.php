<?php

namespace App\Policies;

use App\Models\AutomationRuleRun;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class AutomationRuleRunPolicy
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
    public function view(User $user, AutomationRuleRun $automationRuleRun): Response
    {
        return $this->ownerOnly($user, $automationRuleRun);
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
    public function update(User $user, AutomationRuleRun $automationRuleRun): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AutomationRuleRun $automationRuleRun): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AutomationRuleRun $automationRuleRun): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AutomationRuleRun $automationRuleRun): bool
    {
        return false;
    }

    private function ownerOnly(User $user, AutomationRuleRun $automationRuleRun): Response
    {
        return $automationRuleRun->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
