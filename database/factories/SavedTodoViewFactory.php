<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Models\SavedTodoView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedTodoView>
 */
class SavedTodoViewFactory extends Factory
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
            'name' => fake()->words(2, true),
            'criteria' => [
                'tab' => 'active',
                'search' => '',
                'project' => '',
                'tag' => '',
                'priorityFilter' => '',
                'due' => '',
                'sort' => 'created',
                'direction' => 'desc',
            ],
        ];
    }

    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Due today',
            'criteria' => [
                'tab' => 'active',
                'search' => '',
                'project' => '',
                'tag' => '',
                'priorityFilter' => '',
                'due' => 'today',
                'sort' => 'due',
                'direction' => 'asc',
            ],
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Urgent tasks',
            'criteria' => [
                'tab' => 'active',
                'search' => '',
                'project' => '',
                'tag' => '',
                'priorityFilter' => Priority::Urgent->value,
                'due' => '',
                'sort' => 'priority',
                'direction' => 'desc',
            ],
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Completed tasks',
            'criteria' => [
                'tab' => 'completed',
                'search' => '',
                'project' => '',
                'tag' => '',
                'priorityFilter' => '',
                'due' => '',
                'sort' => 'updated',
                'direction' => 'desc',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function criteria(array $criteria): static
    {
        return $this->state(fn (array $attributes) => [
            'criteria' => $criteria,
        ]);
    }
}
