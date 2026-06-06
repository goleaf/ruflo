<?php

namespace Database\Factories;

use App\Enums\AutomationRuleKind;
use App\Models\AutomationRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationRule>
 */
class AutomationRuleFactory extends Factory
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
            'name' => fake()->words(3, true),
            'kind' => AutomationRuleKind::PromoteOverdueTasks,
            'is_enabled' => true,
            'settings' => [],
        ];
    }

    public function promoteOverdueTasks(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => AutomationRuleKind::PromoteOverdueTasks,
            'settings' => AutomationRuleKind::PromoteOverdueTasks->defaultSettings(),
        ]);
    }

    public function archiveCompletedTasks(int $days = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => AutomationRuleKind::ArchiveCompletedTasks,
            'settings' => ['days' => $days],
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }
}
