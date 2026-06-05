<?php

namespace Database\Seeders;

use App\Enums\Priority;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic, isolated todo workspaces.
 *
 * Every user gets projects, tags, and tasks across the full range of states
 * (active, due today, overdue, upcoming, high priority, completed, archived) so
 * that ownership, filters, due-date buckets, and bulk actions can be exercised
 * by hand and so permission bugs cannot hide behind thin single-user data.
 */
class TodoSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->each(function (User $user): void {
            $work = Project::factory()->for($user)->create(['name' => 'Work', 'color' => 'blue']);
            $home = Project::factory()->for($user)->create(['name' => 'Home', 'color' => 'green']);
            Project::factory()->for($user)->archived()->create(['name' => 'Old plans', 'color' => 'zinc']);

            $urgent = Tag::factory()->for($user)->create(['name' => 'urgent', 'color' => 'red']);
            $waiting = Tag::factory()->for($user)->create(['name' => 'waiting', 'color' => 'amber']);

            Todo::factory()->for($user)->for($work)->priority(Priority::High)->dueOn()
                ->create(['title' => 'Review the current flow'])
                ->tags()->attach($urgent);

            Todo::factory()->for($user)->for($work)->overdue()->priority(Priority::Urgent)
                ->create(['title' => 'Send the overdue report'])
                ->tags()->attach([$urgent->id, $waiting->id]);

            Todo::factory()->for($user)->for($home)->upcoming()
                ->create(['title' => 'Plan the weekend']);

            Todo::factory()->for($user)
                ->create(['title' => 'Capture a loose idea']);

            Todo::factory()->for($user)->for($work)->completed()
                ->create(['title' => 'Ship one small improvement']);

            Todo::factory()->for($user)->for($home)->archived()
                ->create(['title' => 'Last month\'s checklist']);
        });
    }
}
