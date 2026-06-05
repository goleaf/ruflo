<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->word(),
            'color' => fake()->randomElement(['zinc', 'blue', 'green', 'amber', 'red', 'purple']),
        ];
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

    public function urgent(): static
    {
        return $this->named('urgent')->color('red');
    }

    public function waiting(): static
    {
        return $this->named('waiting')->color('amber');
    }
}
