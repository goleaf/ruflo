<?php

namespace Database\Seeders;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectMembershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'demo'])) {
            return;
        }

        $avery = User::query()
            ->where('email', (string) config('demo.login_panel.users.0.email', 'test@example.com'))
            ->first();
        $morgan = User::query()
            ->where('email', (string) config('demo.login_panel.users.1.email', 'second@example.com'))
            ->first();

        if (! $avery instanceof User || ! $morgan instanceof User) {
            return;
        }

        DB::transaction(function () use ($avery, $morgan): void {
            $this->shareProject($avery, 'Work', $morgan, ProjectRole::Editor);
            $this->shareProject($avery, 'Home', $morgan, ProjectRole::Viewer);
            $this->shareProject($morgan, 'Work', $avery, ProjectRole::Manager);
        });
    }

    private function shareProject(User $owner, string $projectName, User $member, ProjectRole $role): void
    {
        $project = Project::query()
            ->where('user_id', $owner->id)
            ->where('name', $projectName)
            ->first();

        if (! $project instanceof Project) {
            return;
        }

        ProjectMembership::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'user_id' => $member->id,
            ],
            [
                'added_by_user_id' => $owner->id,
                'role' => $role,
                'removed_at' => null,
            ],
        );
    }
}
