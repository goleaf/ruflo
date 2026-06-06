<?php

namespace Database\Seeders;

use App\Models\ActivityRecord;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'demo'])) {
            return;
        }

        User::query()
            ->whereIn('email', [
                (string) config('demo.login_panel.users.0.email', 'test@example.com'),
                (string) config('demo.login_panel.users.1.email', 'second@example.com'),
            ])
            ->get()
            ->each(fn (User $user): ActivityRecord => $this->seedUser($user));
    }

    private function seedUser(User $user): ActivityRecord
    {
        if (ActivityRecord::query()->ownedBy($user)->exists()) {
            return ActivityRecord::query()->ownedBy($user)->latest('occurred_at')->firstOrFail();
        }

        $todos = Todo::query()
            ->withTrashed()
            ->ownedBy($user)
            ->latest('updated_at')
            ->limit(3)
            ->get();

        foreach ($todos as $index => $todo) {
            ActivityRecord::factory()
                ->forTodo($todo, $index === 0 ? 'todo.updated' : 'todo.created')
                ->create([
                    'occurred_at' => now()->subMinutes(($index + 1) * 15),
                ]);
        }

        return ActivityRecord::factory()
            ->completedCleared($user, 2)
            ->create(['occurred_at' => now()->subMinutes(60)]);
    }
}
