<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\TaskTemplateKind;
use App\Models\TodoTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TodoTemplate>
 */
class TodoTemplateFactory extends Factory
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
            'name' => fake()->unique()->words(3, true),
            'kind' => TaskTemplateKind::Task,
            'visibility' => 'private',
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(8),
            'priority' => Priority::Normal,
            'due_offset_days' => null,
            'project_name' => null,
            'checklist_items' => [],
        ];
    }

    public function task(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => TaskTemplateKind::Task,
            'checklist_items' => [],
        ]);
    }

    public function project(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => TaskTemplateKind::Project,
            'project_name' => 'Project launch',
            'checklist_items' => [
                'Create the kickoff task',
                'Confirm the owner',
            ],
        ]);
    }

    public function checklist(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => TaskTemplateKind::Checklist,
            'checklist_items' => [
                'Open the template',
                'Complete the first item',
            ],
        ]);
    }

    public function routine(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => TaskTemplateKind::Routine,
            'title' => 'Run the weekly review',
            'due_offset_days' => 0,
            'checklist_items' => [
                'Review overdue tasks',
                'Pick three priorities',
                'Archive stale work',
            ],
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'private',
        ]);
    }

    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'shared',
        ]);
    }

    public function dueIn(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'due_offset_days' => $days,
        ]);
    }

    public function heavyChecklist(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => TaskTemplateKind::Checklist,
            'checklist_items' => array_map(
                fn (int $index): string => 'Checklist item '.$index,
                range(1, 10),
            ),
        ]);
    }

    public function longName(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => str_repeat('x', 80),
        ]);
    }
}
