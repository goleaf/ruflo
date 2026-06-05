<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! $this->canSeedDemoUsers()) {
            return;
        }

        foreach (config('demo.login_panel.users', []) as $demoUser) {
            $user = User::query()
                ->where('email', $demoUser['email'])
                ->first() ?? new User;

            $user->forceFill([
                'name' => $demoUser['name'],
                'email' => $demoUser['email'],
                'email_verified_at' => $user->email_verified_at ?? now(),
                'password' => (string) config('demo.login_panel.password', 'password'),
            ])->save();
        }
    }

    private function canSeedDemoUsers(): bool
    {
        if (! (bool) config('demo.login_panel.enabled', true)) {
            return false;
        }

        return in_array((string) config('app.env'), config('demo.login_panel.environments', []), true);
    }
}
