<?php

namespace Database\Factories;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\Todo;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'todo_id' => Todo::factory(),
            'remind_at' => now()->addHour()->startOfMinute(),
            'status' => ReminderStatus::Pending,
            'processed_at' => null,
            'skipped_at' => null,
            'skipped_reason' => null,
            'last_error' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Reminder $reminder): void {
            $this->syncOwnerFromTodo($reminder);
        })->afterCreating(function (Reminder $reminder): void {
            $this->syncOwnerFromTodo($reminder);
            $reminder->save();
        });
    }

    public function forTodo(Todo $todo): static
    {
        return $this
            ->for($todo, 'todo')
            ->state(fn (array $attributes) => [
                'user_id' => $todo->user_id,
            ]);
    }

    public function due(DateTimeInterface|string|null $date = null): static
    {
        $date ??= now()->subMinute();

        return $this->state(fn (array $attributes) => [
            'remind_at' => $date,
            'status' => ReminderStatus::Pending,
            'processed_at' => null,
            'skipped_at' => null,
            'skipped_reason' => null,
            'last_error' => null,
        ]);
    }

    public function future(DateTimeInterface|string|null $date = null): static
    {
        $date ??= now()->addHour();

        return $this->state(fn (array $attributes) => [
            'remind_at' => $date,
            'status' => ReminderStatus::Pending,
            'processed_at' => null,
            'skipped_at' => null,
            'skipped_reason' => null,
            'last_error' => null,
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReminderStatus::Processed,
            'processed_at' => now(),
            'skipped_at' => null,
            'skipped_reason' => null,
            'last_error' => null,
        ]);
    }

    public function skipped(string $reason = 'task_not_actionable'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReminderStatus::Skipped,
            'processed_at' => null,
            'skipped_at' => now(),
            'skipped_reason' => $reason,
            'last_error' => null,
        ]);
    }

    private function syncOwnerFromTodo(Reminder $reminder): void
    {
        if ($reminder->user_id !== null) {
            return;
        }

        $todo = $reminder->todo;

        if ($todo instanceof Todo) {
            $reminder->user_id = $todo->user_id;
        }
    }
}
