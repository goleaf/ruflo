<?php

namespace Database\Factories;

use App\Enums\HabitFrequency;
use App\Models\Goal;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Habit>
 */
class HabitFactory extends Factory
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
            'goal_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(8),
            'frequency' => HabitFrequency::Daily,
            'target_count' => 1,
            'starts_on' => null,
            'archived_at' => null,
        ];
    }

    public function titled(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }

    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => HabitFrequency::Daily,
            'target_count' => 1,
        ]);
    }

    public function weekly(int $targetCount = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => HabitFrequency::Weekly,
            'target_count' => $targetCount,
        ]);
    }

    public function targetCount(int $targetCount): static
    {
        return $this->state(fn (array $attributes) => [
            'target_count' => $targetCount,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }

    public function forGoal(Goal $goal): static
    {
        return $this
            ->for($goal, 'goal')
            ->state(fn (array $attributes) => [
                'user_id' => $goal->user_id,
            ]);
    }
}
