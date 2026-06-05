<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(2, true),
            'color' => fake()->randomElement(['zinc', 'blue', 'green', 'amber', 'red', 'purple']),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => null,
        ]);
    }

    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    public function color(string $color): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => $color,
        ]);
    }

    public function work(): static
    {
        return $this->named('Work')->color('blue');
    }

    public function home(): static
    {
        return $this->named('Home')->color('green');
    }
}
