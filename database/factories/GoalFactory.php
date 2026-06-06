<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\Project;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
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
            'project_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(10),
            'target_date' => null,
            'completed_at' => null,
            'archived_at' => null,
        ];
    }

    public function titled(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }

    public function targetDate(DateTimeInterface|string|null $date = null): static
    {
        $targetDate = $date instanceof DateTimeInterface
            ? $date->format('Y-m-d')
            : ($date ?? today()->addWeeks(2)->toDateString());

        return $this->state(fn (array $attributes) => [
            'target_date' => $targetDate,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
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
}
