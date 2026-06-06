<?php

namespace Database\Factories;

use App\Enums\TimeEntrySource;
use App\Enums\TimeEntryStatus;
use App\Models\PomodoroSession;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
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
            'todo_id' => null,
            'project_id' => null,
            'pomodoro_session_id' => null,
            'duration_seconds' => fake()->numberBetween(15, 120) * 60,
            'source' => TimeEntrySource::Manual,
            'status' => TimeEntryStatus::Completed,
            'entry_date' => today()->subDays(fake()->numberBetween(0, 5))->toDateString(),
            'started_at' => null,
            'stopped_at' => null,
            'notes' => fake()->optional(0.4)->sentence(8),
        ];
    }

    public function forTodo(Todo $todo): static
    {
        return $this
            ->for($todo, 'todo')
            ->state(fn (array $attributes) => [
                'user_id' => $todo->user_id,
                'project_id' => $todo->project_id,
            ]);
    }

    public function forProject(Project $project): static
    {
        return $this
            ->for($project, 'project')
            ->state(fn (array $attributes) => [
                'user_id' => $project->user_id,
                'todo_id' => null,
            ]);
    }

    public function manual(int $minutes = 45): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_seconds' => $minutes * 60,
            'source' => TimeEntrySource::Manual,
            'status' => TimeEntryStatus::Completed,
            'started_at' => null,
            'stopped_at' => null,
        ]);
    }

    public function timer(int $minutes = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_seconds' => $minutes * 60,
            'source' => TimeEntrySource::Timer,
            'status' => TimeEntryStatus::Completed,
            'started_at' => now()->subMinutes($minutes),
            'stopped_at' => now(),
        ]);
    }

    public function running(int $elapsedMinutes = 12): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_seconds' => 0,
            'source' => TimeEntrySource::Timer,
            'status' => TimeEntryStatus::Running,
            'entry_date' => today()->toDateString(),
            'started_at' => now()->subMinutes($elapsedMinutes),
            'stopped_at' => null,
        ]);
    }

    public function discarded(int $minutes = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_seconds' => $minutes * 60,
            'source' => TimeEntrySource::Timer,
            'status' => TimeEntryStatus::Discarded,
            'started_at' => now()->subMinutes($minutes),
            'stopped_at' => now(),
        ]);
    }

    public function fromPomodoro(PomodoroSession $session): static
    {
        $projectId = $session->relationLoaded('todo')
            ? $session->todo?->project_id
            : Todo::query()->whereKey($session->todo_id)->value('project_id');

        return $this
            ->for($session, 'pomodoroSession')
            ->state(fn (array $attributes) => [
                'user_id' => $session->user_id,
                'todo_id' => $session->todo_id,
                'project_id' => $projectId,
                'duration_seconds' => $session->elapsed_seconds,
                'source' => TimeEntrySource::Pomodoro,
                'status' => TimeEntryStatus::Completed,
                'entry_date' => ($session->completed_at ?? now())->toDateString(),
                'started_at' => $session->started_at,
                'stopped_at' => $session->completed_at,
            ]);
    }

    public function demo(): static
    {
        return $this->manual(35)->state(fn (array $attributes) => [
            'entry_date' => today()->toDateString(),
            'notes' => 'Reviewed task flow and captured the next improvement.',
        ]);
    }
}
