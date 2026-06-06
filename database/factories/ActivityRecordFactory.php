<?php

namespace Database\Factories;

use App\Models\ActivityRecord;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityRecord>
 */
final class ActivityRecordFactory extends Factory
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
            'actor_id' => null,
            'event' => 'todo.updated',
            'subject_type' => null,
            'subject_id' => null,
            'subject_title' => fake()->sentence(4),
            'metadata' => [],
            'occurred_at' => now()->subMinutes(fake()->numberBetween(1, 240)),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'actor_id' => $user->id,
        ]);
    }

    public function forTodo(Todo $todo, string $event = 'todo.updated'): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $todo->user_id,
            'actor_id' => $todo->user_id,
            'event' => $event,
            'subject_type' => $todo->getMorphClass(),
            'subject_id' => $todo->id,
            'subject_title' => $todo->title,
        ]);
    }

    public function todoCreated(Todo $todo): static
    {
        return $this->forTodo($todo, 'todo.created');
    }

    public function todoUpdated(Todo $todo): static
    {
        return $this->forTodo($todo)->state(fn (array $attributes) => [
            'metadata' => [
                'changes' => [
                    'title' => ['old' => 'Old title', 'new' => $todo->title],
                ],
            ],
        ]);
    }

    public function todoDeleted(Todo $todo): static
    {
        return $this->forTodo($todo, 'todo.deleted')->state(fn (array $attributes) => [
            'metadata' => ['deleted' => true],
        ]);
    }

    public function completedCleared(User $user, int $count = 2): static
    {
        return $this->forUser($user)->state(fn (array $attributes) => [
            'event' => 'todos.completed_cleared',
            'subject_type' => null,
            'subject_id' => null,
            'subject_title' => null,
            'metadata' => ['count' => $count],
        ]);
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function withChanges(array $changes): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'todo.updated',
            'metadata' => ['changes' => $changes],
        ]);
    }
}
