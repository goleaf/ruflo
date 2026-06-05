<?php

namespace Database\Seeders;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds isolated todo workspaces.
 *
 * Two users with separate private tasks are required so that ownership and
 * cross-user isolation can be exercised by hand and by tests. Single-user
 * seed data hides permission bugs.
 */
class TodoSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->each(function (User $user): void {
            Todo::factory()
                ->for($user)
                ->createMany([
                    ['title' => 'Review today\'s flow'],
                    ['title' => 'Ship one small improvement'],
                    ['title' => 'Plan tomorrow\'s focus'],
                    ['title' => 'Clear completed notes', 'is_completed' => true],
                ]);
        });
    }
}
