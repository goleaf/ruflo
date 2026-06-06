<?php

namespace App\Policies;

use App\Models\AutomationRule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class AutomationRulePolicy
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
    public function view(User $user, AutomationRule $automationRule): Response
    {
        return $this->ownerOnly($user, $automationRule);
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
    public function update(User $user, AutomationRule $automationRule): Response
    {
        return $this->ownerOnly($user, $automationRule);
    }

    /**
     * Determine whether the user can manually run the rule.
     */
    public function run(User $user, AutomationRule $automationRule): Response
    {
        return $this->ownerOnly($user, $automationRule);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AutomationRule $automationRule): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AutomationRule $automationRule): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AutomationRule $automationRule): bool
    {
        return false;
    }

    private function ownerOnly(User $user, AutomationRule $automationRule): Response
    {
        return $automationRule->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
