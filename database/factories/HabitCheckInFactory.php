<?php

namespace Database\Factories;

use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HabitCheckIn>
 */
class HabitCheckInFactory extends Factory
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
            'habit_id' => Habit::factory(),
            'occurred_on' => today()->toDateString(),
            'checked_at' => now(),
        ];
    }

    public function forHabit(Habit $habit): static
    {
        return $this
            ->for($habit, 'habit')
            ->state(fn (array $attributes) => [
                'user_id' => $habit->user_id,
            ]);
    }

    public function occurredOn(DateTimeInterface|string $date): static
    {
        $occurredOn = $date instanceof DateTimeInterface
            ? $date->format('Y-m-d')
            : $date;

        return $this->state(fn (array $attributes) => [
            'occurred_on' => $occurredOn,
            'checked_at' => now(),
        ]);
    }

    public function today(): static
    {
        return $this->occurredOn(today()->toDateString());
    }

    public function yesterday(): static
    {
        return $this->occurredOn(today()->subDay()->toDateString());
    }
}
