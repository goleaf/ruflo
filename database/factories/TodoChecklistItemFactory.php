<?php

namespace Database\Factories;

use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TodoChecklistItem>
 */
class TodoChecklistItemFactory extends Factory
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
            'todo_id' => fn (array $attributes) => Todo::factory()->state([
                'user_id' => $attributes['user_id'],
            ]),
            'title' => fake()->sentence(4),
            'is_completed' => false,
            'completed_at' => null,
            'position' => fake()->numberBetween(1, 5),
        ];
    }

    public function forTodo(Todo $todo): static
    {
        return $this
            ->for($todo, 'todo')
            ->state(fn (array $attributes) => [
                'user_id' => $todo->user_id,
            ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => false,
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    public function position(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }

    public function longTitle(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => str_repeat('x', 120),
        ]);
    }
}
