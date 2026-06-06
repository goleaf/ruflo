<?php

namespace Database\Factories;

use App\Enums\AutomationRunStatus;
use App\Models\AutomationRule;
use App\Models\AutomationRuleRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationRuleRun>
 */
class AutomationRuleRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'automation_rule_id' => AutomationRule::factory(),
            'status' => AutomationRunStatus::Completed,
            'dry_run' => false,
            'matched_count' => 0,
            'changed_count' => 0,
            'skipped_count' => 0,
            'details' => [],
            'message' => __('automation.runs.messages.completed'),
            'started_at' => now(),
            'finished_at' => now(),
        ];
    }

    public function forRule(AutomationRule $rule): static
    {
        return $this
            ->for($rule, 'rule')
            ->state(fn (array $attributes) => [
                'user_id' => $rule->user_id,
            ]);
    }

    public function dryRun(): static
    {
        return $this->state(fn (array $attributes) => [
            'dry_run' => true,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AutomationRunStatus::Disabled,
            'message' => __('automation.runs.messages.disabled'),
        ]);
    }
}
