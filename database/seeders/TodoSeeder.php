<?php

namespace Database\Seeders;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Seeder;

class TodoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->each(function (User $user): void {
            Todo::factory()
                ->count(3)
                ->for($user)
                ->sequence(
                    ['title' => 'Review today\'s flow'],
                    ['title' => 'Ship one small improvement'],
                    ['title' => 'Clear completed notes', 'is_completed' => true],
                )
                ->create();
        });
    }
}
