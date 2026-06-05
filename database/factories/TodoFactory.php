<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Todo>
 */
class TodoFactory extends Factory
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
            'title' => fake()->sentence(3),
            'is_completed' => false,
            'priority' => Priority::Normal,
            'due_date' => null,
        ];
    }

    /**
     * Indicate the task's priority.
     */
    public function priority(Priority $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }

    /**
     * Indicate the task is due on a given date (defaults to today).
     */
    public function dueOn(?string $date = null): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $date ?? today()->toDateString(),
        ]);
    }

    /**
     * Indicate the task is overdue (due yesterday and still active).
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => today()->subDay()->toDateString(),
            'is_completed' => false,
        ]);
    }

    /**
     * Indicate the task is due in the near future.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => today()->addDays(3)->toDateString(),
        ]);
    }

    /**
     * Indicate that the todo is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => true,
        ]);
    }

    /**
     * Indicate that the todo is archived. Completion state is preserved so an
     * archived-while-completed task restores back to completed.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
