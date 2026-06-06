<?php

namespace Database\Factories;

use App\Models\Todo;
use App\Models\TodoDependency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TodoDependency>
 */
class TodoDependencyFactory extends Factory
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
            'todo_id' => Todo::factory(),
            'depends_on_todo_id' => Todo::factory(),
        ];
    }

    public function forTodos(Todo $todo, Todo $dependsOn): static
    {
        return $this
            ->for($todo, 'todo')
            ->for($dependsOn, 'blocker')
            ->state(fn (array $attributes) => [
                'user_id' => $todo->user_id,
                'depends_on_todo_id' => $dependsOn->id,
            ]);
    }

    public function open(): static
    {
        return $this->afterCreating(function (TodoDependency $dependency): void {
            $dependency->blocker?->forceFill([
                'is_completed' => false,
                'archived_at' => null,
                'deleted_at' => null,
            ])->save();
        });
    }

    public function resolved(): static
    {
        return $this->afterCreating(function (TodoDependency $dependency): void {
            $dependency->blocker?->forceFill(['is_completed' => true])->save();
        });
    }
}
