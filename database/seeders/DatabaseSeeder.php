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
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // A second user proves private isolation: their tasks must never be
        // visible from the first user's workspace.
        User::factory()->create([
            'name' => 'Second User',
            'email' => 'second@example.com',
        ]);

        $this->call(TodoSeeder::class);
    }
}
