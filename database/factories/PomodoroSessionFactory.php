<?php

namespace Database\Factories;

use App\Enums\PomodoroSessionStatus;
use App\Models\PomodoroSession;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PomodoroSession>
 */
class PomodoroSessionFactory extends Factory
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
            'duration_minutes' => 25,
            'elapsed_seconds' => 0,
            'status' => PomodoroSessionStatus::Running,
            'started_at' => now(),
            'last_started_at' => now(),
            'paused_at' => null,
            'completed_at' => null,
            'abandoned_at' => null,
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

    public function duration(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_minutes' => $minutes,
        ]);
    }

    public function running(int $elapsedSeconds = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'elapsed_seconds' => $elapsedSeconds,
            'status' => PomodoroSessionStatus::Running,
            'started_at' => now()->subSeconds($elapsedSeconds),
            'last_started_at' => now(),
            'paused_at' => null,
            'completed_at' => null,
            'abandoned_at' => null,
        ]);
    }

    public function paused(int $elapsedSeconds = 600): static
    {
        return $this->state(fn (array $attributes) => [
            'elapsed_seconds' => $elapsedSeconds,
            'status' => PomodoroSessionStatus::Paused,
            'started_at' => now()->subSeconds($elapsedSeconds),
            'last_started_at' => null,
            'paused_at' => now(),
            'completed_at' => null,
            'abandoned_at' => null,
        ]);
    }

    public function completed(int $elapsedSeconds = 1500): static
    {
        return $this->state(fn (array $attributes) => [
            'elapsed_seconds' => $elapsedSeconds,
            'status' => PomodoroSessionStatus::Completed,
            'started_at' => now()->subSeconds($elapsedSeconds),
            'last_started_at' => null,
            'paused_at' => null,
            'completed_at' => now(),
            'abandoned_at' => null,
        ]);
    }

    public function abandoned(int $elapsedSeconds = 300): static
    {
        return $this->state(fn (array $attributes) => [
            'elapsed_seconds' => $elapsedSeconds,
            'status' => PomodoroSessionStatus::Abandoned,
            'started_at' => now()->subSeconds($elapsedSeconds),
            'last_started_at' => null,
            'paused_at' => null,
            'completed_at' => null,
            'abandoned_at' => now(),
        ]);
    }

    public function demo(): static
    {
        return $this->paused(480)->duration(25);
    }
}
