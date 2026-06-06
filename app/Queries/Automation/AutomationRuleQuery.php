<?php

namespace App\Queries\Automation;

use App\Models\AutomationRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class AutomationRuleQuery
{
    /**
     * @return Builder<AutomationRule>
     */
    public function for(User $user): Builder
    {
        return AutomationRule::query()
            ->ownedBy($user)
            ->with('latestRun')
            ->withCount('runs')
            ->latest();
    }

    public function findFor(User $user, int $automationRuleId): AutomationRule
    {
        return $this->for($user)->findOrFail($automationRuleId);
    }
}
