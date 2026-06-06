<?php

namespace Database\Factories;

use App\Models\Todo;
use App\Models\TodoComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TodoComment>
 */
class TodoCommentFactory extends Factory
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
            'author_id' => fn (array $attributes) => $attributes['user_id'],
            'body' => fake()->paragraph(),
            'edited_at' => null,
        ];
    }

    public function forTodo(Todo $todo): static
    {
        return $this
            ->for($todo)
            ->state(fn (array $attributes): array => [
                'user_id' => $todo->user_id,
                'author_id' => $todo->user_id,
            ]);
    }

    public function authoredBy(User $author): static
    {
        return $this->state(fn (array $attributes): array => [
            'author_id' => $author->id,
        ]);
    }

    public function edited(): static
    {
        return $this->state(fn (array $attributes): array => [
            'edited_at' => now(),
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'deleted_at' => now(),
        ]);
    }

    public function demoBody(string $body = 'Reviewed the next action and left a concise collaboration note.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'body' => $body,
        ]);
    }
}
