<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

        $demoUsers = collect(config('demo.login_panel.users', []));
        $existingUsers = User::query()
            ->whereIn('email', $demoUsers->pluck('email')->all())
            ->get()
            ->keyBy('email');

        DB::transaction(function () use ($demoUsers, $existingUsers): void {
            foreach ($demoUsers as $index => $demoUser) {
                $user = $existingUsers->get($demoUser['email']) ?? new User;

                $user->forceFill([
                    'name' => $demoUser['name'],
                    'email' => $demoUser['email'],
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'is_admin' => $index === 0,
                    'password' => (string) config('demo.login_panel.password', 'password'),
                ])->save();
            }
        });
    }

    private function canSeedDemoUsers(): bool
    {
        if (! (bool) config('demo.login_panel.enabled', true)) {
            return false;
        }

        return in_array((string) config('app.env'), config('demo.login_panel.environments', []), true);
    }
}
