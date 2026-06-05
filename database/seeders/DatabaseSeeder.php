<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (in_array((string) config('app.env'), config('demo.login_panel.environments', []), true)) {
            foreach (config('demo.login_panel.users', []) as $demoUser) {
                User::factory()->create([
                    'name' => $demoUser['name'],
                    'email' => $demoUser['email'],
                    'password' => (string) config('demo.login_panel.password'),
                ]);
            }
        }

        $this->call(TodoSeeder::class);
    }
}
