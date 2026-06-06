<?php

namespace App\Actions\Automation;

use App\Enums\AutomationRuleKind;
use App\Models\AutomationRule;
use App\Models\User;
use App\Rules\Automation\AutomationRuleName;
use Illuminate\Validation\ValidationException;

final class CreateAutomationRule
{
    public function handle(User $user, string $name, AutomationRuleKind $kind): AutomationRule
    {
        $normalizedName = AutomationRuleName::normalize($name)
            ?? throw ValidationException::withMessages([
                'name' => __('automation.validation.rule_name'),
            ]);

        if ($user->automationRules()->where('name', $normalizedName)->exists()) {
            throw ValidationException::withMessages([
                'name' => __('automation.validation.rule_name_unique'),
            ]);
        }

        return $user->automationRules()->create([
            'name' => $normalizedName,
            'kind' => $kind,
            'is_enabled' => true,
            'settings' => $kind->defaultSettings(),
        ]);
    }
}
