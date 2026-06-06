<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoalMilestone>
 */
class GoalMilestoneFactory extends Factory
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
            'goal_id' => Goal::factory(),
            'title' => fake()->sentence(3),
            'target_date' => null,
            'position' => fake()->numberBetween(1, 5),
            'completed_at' => null,
        ];
    }

    public function forGoal(Goal $goal): static
    {
        return $this
            ->for($goal, 'goal')
            ->state(fn (array $attributes) => [
                'user_id' => $goal->user_id,
            ]);
    }

    public function titled(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }

    public function position(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }

    public function targetDate(DateTimeInterface|string|null $date = null): static
    {
        $targetDate = $date instanceof DateTimeInterface
            ? $date->format('Y-m-d')
            : ($date ?? today()->addWeek()->toDateString());

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

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => null,
        ]);
    }
}
