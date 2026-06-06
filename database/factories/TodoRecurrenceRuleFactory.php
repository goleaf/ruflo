<?php

namespace Database\Factories;

use App\Enums\RecurrenceEndType;
use App\Enums\RecurrenceFrequency;
use App\Enums\RecurrenceWeekday;
use App\Models\Todo;
use App\Models\TodoRecurrenceRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TodoRecurrenceRule>
 */
class TodoRecurrenceRuleFactory extends Factory
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
            'frequency' => RecurrenceFrequency::Daily,
            'interval' => 1,
            'starts_on' => today()->toDateString(),
            'weekdays' => [],
            'month_day' => null,
            'end_type' => RecurrenceEndType::Never,
            'ends_on' => null,
            'max_occurrences' => null,
            'is_enabled' => true,
            'last_generated_until' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (TodoRecurrenceRule $rule): void {
            $this->syncOwnerFromTodo($rule);
        })->afterCreating(function (TodoRecurrenceRule $rule): void {
            $this->syncOwnerFromTodo($rule);
            $rule->save();
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

    /**
     * @param  list<RecurrenceWeekday|string>  $weekdays
     */
    public function weekly(array $weekdays = []): static
    {
        $weekdays = $weekdays === []
            ? [RecurrenceWeekday::Monday, RecurrenceWeekday::Wednesday]
            : $weekdays;

        return $this->state(fn (array $attributes) => [
            'frequency' => RecurrenceFrequency::Weekly,
            'weekdays' => array_map(
                fn (RecurrenceWeekday|string $weekday): string => $weekday instanceof RecurrenceWeekday ? $weekday->value : $weekday,
                $weekdays,
            ),
            'month_day' => null,
        ]);
    }

    public function monthly(int $day = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => RecurrenceFrequency::Monthly,
            'weekdays' => [],
            'month_day' => $day,
        ]);
    }

    public function endingOn(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'end_type' => RecurrenceEndType::OnDate,
            'ends_on' => $date,
            'max_occurrences' => null,
        ]);
    }

    public function afterOccurrences(int $count = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'end_type' => RecurrenceEndType::AfterOccurrences,
            'ends_on' => null,
            'max_occurrences' => $count,
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    public function generatedUntil(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'last_generated_until' => $date,
        ]);
    }

    private function syncOwnerFromTodo(TodoRecurrenceRule $rule): void
    {
        $todo = $rule->todo;

        if (! $todo instanceof Todo) {
            return;
        }

        if ($rule->user_id === null) {
            $rule->user_id = $todo->user_id;

            return;
        }

        if ($todo->user_id !== $rule->user_id) {
            $todo->forceFill(['user_id' => $rule->user_id])->save();
        }
    }
}
