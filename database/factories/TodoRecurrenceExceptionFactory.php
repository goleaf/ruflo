<?php

namespace Database\Factories;

use App\Enums\RecurrenceExceptionType;
use App\Models\Todo;
use App\Models\TodoRecurrenceException;
use App\Models\TodoRecurrenceRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TodoRecurrenceException>
 */
class TodoRecurrenceExceptionFactory extends Factory
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
            'todo_recurrence_rule_id' => TodoRecurrenceRule::factory(),
            'todo_id' => null,
            'type' => RecurrenceExceptionType::Skipped,
            'original_occurs_on' => today()->addDay()->toDateString(),
            'adjusted_occurs_on' => null,
            'note' => fake()->optional()->sentence(6),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (TodoRecurrenceException $exception): void {
            $this->syncOwnerFromRuleOrTodo($exception);
        })->afterCreating(function (TodoRecurrenceException $exception): void {
            $this->syncOwnerFromRuleOrTodo($exception);
            $exception->save();
        });
    }

    public function forRule(TodoRecurrenceRule $rule, string $date): static
    {
        return $this
            ->for($rule, 'recurrenceRule')
            ->state(fn (array $attributes) => [
                'user_id' => $rule->user_id,
                'original_occurs_on' => $date,
            ]);
    }

    public function forOccurrence(Todo $occurrence): static
    {
        return $this
            ->for($occurrence, 'todo')
            ->state(fn (array $attributes) => [
                'user_id' => $occurrence->user_id,
                'todo_recurrence_rule_id' => $occurrence->recurrence_rule_id,
                'original_occurs_on' => $occurrence->recurrence_occurs_on?->toDateString() ?? today()->addDay()->toDateString(),
            ]);
    }

    public function skipped(?string $note = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => RecurrenceExceptionType::Skipped,
            'adjusted_occurs_on' => null,
            'note' => $note,
        ]);
    }

    public function moved(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => RecurrenceExceptionType::Moved,
            'adjusted_occurs_on' => $date,
        ]);
    }

    public function edited(?string $note = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => RecurrenceExceptionType::Edited,
            'adjusted_occurs_on' => null,
            'note' => $note,
        ]);
    }

    private function syncOwnerFromRuleOrTodo(TodoRecurrenceException $exception): void
    {
        if ($exception->todo instanceof Todo) {
            if ($exception->user_id === null) {
                $exception->user_id = $exception->todo->user_id;
            }

            if ($exception->todo_recurrence_rule_id === null) {
                $exception->todo_recurrence_rule_id = $exception->todo->recurrence_rule_id;
            }

            if ($exception->original_occurs_on === null) {
                $exception->original_occurs_on = $exception->todo->recurrence_occurs_on?->toDateString();
            }
        }

        if ($exception->recurrenceRule instanceof TodoRecurrenceRule) {
            if ($exception->user_id === null) {
                $exception->user_id = $exception->recurrenceRule->user_id;
            }
        }
    }
}
