<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use DateTimeInterface;
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
            'inbox_captured_at' => null,
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

    public function lowPriority(): static
    {
        return $this->priority(Priority::Low);
    }

    public function normalPriority(): static
    {
        return $this->priority(Priority::Normal);
    }

    public function highPriority(): static
    {
        return $this->priority(Priority::High);
    }

    public function urgentPriority(): static
    {
        return $this->priority(Priority::Urgent);
    }

    public function focusCandidate(): static
    {
        return $this
            ->highPriority()
            ->dueToday()
            ->active();
    }

    /**
     * Indicate the task is due on a given date (defaults to today).
     */
    public function dueOn(DateTimeInterface|string|null $date = null): static
    {
        $dueDate = $date instanceof DateTimeInterface
            ? $date->format('Y-m-d')
            : ($date ?? today()->toDateString());

        return $this->state(fn (array $attributes) => [
            'due_date' => $dueDate,
        ]);
    }

    public function dueToday(): static
    {
        return $this->dueOn();
    }

    public function withoutDueDate(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => null,
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

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => false,
            'archived_at' => null,
            'deleted_at' => null,
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
     * archived-while-completed task unarchives back to completed.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }

    /**
     * Indicate the task was quickly captured and still needs triage.
     */
    public function inbox(DateTimeInterface|string|null $capturedAt = null): static
    {
        $capturedAt ??= now();

        return $this->state(fn (array $attributes) => [
            'is_completed' => false,
            'archived_at' => null,
            'deleted_at' => null,
            'project_id' => null,
            'due_date' => null,
            'priority' => Priority::Normal,
            'inbox_captured_at' => $capturedAt,
        ]);
    }

    /**
     * Indicate the task has already been organized out of the inbox.
     */
    public function triaged(): static
    {
        return $this->state(fn (array $attributes) => [
            'inbox_captured_at' => null,
        ]);
    }

    public function longTitle(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => str_repeat('x', 120),
        ]);
    }

    public function forProject(Project $project): static
    {
        return $this
            ->for($project, 'project')
            ->state(fn (array $attributes) => [
                'user_id' => $project->user_id,
            ]);
    }

    public function forTag(Tag $tag): static
    {
        return $this
            ->state(fn (array $attributes) => [
                'user_id' => $tag->user_id,
            ])
            ->afterCreating(function (Todo $todo) use ($tag): void {
                $todo->tags()->syncWithoutDetaching([$tag->id]);
            });
    }

    public function withTags(Tag ...$tags): static
    {
        return $this->afterCreating(function (Todo $todo) use ($tags): void {
            $tagIds = collect($tags)
                ->filter(fn (Tag $tag): bool => $tag->user_id === $todo->user_id)
                ->pluck('id')
                ->all();

            $todo->tags()->syncWithoutDetaching($tagIds);
        });
    }
}
