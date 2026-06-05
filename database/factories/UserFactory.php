<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'is_admin' => false,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withPassword(string $password): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => Hash::make($password),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    public function demoPrimary(): static
    {
        return $this->withPassword((string) config('demo.login_panel.password', 'password'))
            ->admin()
            ->state(fn (array $attributes) => [
                'name' => (string) config('demo.login_panel.users.0.name', 'Test User'),
                'email' => (string) config('demo.login_panel.users.0.email', 'test@example.com'),
                'email_verified_at' => now(),
            ]);
    }

    public function demoSecondary(): static
    {
        return $this->withPassword((string) config('demo.login_panel.password', 'password'))
            ->state(fn (array $attributes) => [
                'name' => (string) config('demo.login_panel.users.1.name', 'Second User'),
                'email' => (string) config('demo.login_panel.users.1.email', 'second@example.com'),
                'email_verified_at' => now(),
                'is_admin' => false,
            ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
