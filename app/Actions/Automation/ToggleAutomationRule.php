<?php

namespace App\Actions\Automation;

use App\Models\AutomationRule;

final class ToggleAutomationRule
{
    public function handle(AutomationRule $automationRule, bool $isEnabled): AutomationRule
    {
        $automationRule->forceFill([
            'is_enabled' => $isEnabled,
        ])->save();

        return $automationRule->refresh();
    }
}
