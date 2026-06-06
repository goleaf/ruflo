<?php

namespace Database\Seeders;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectInvitationSeeder extends Seeder
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
            $averyWork = $this->project($avery, 'Work');
            $averyHome = $this->project($avery, 'Home');
            $morganWork = $this->project($morgan, 'Work');

            if ($averyWork instanceof Project) {
                $this->upsertInvitation($averyWork, $avery, ProjectRole::Manager, 'RufloDemoInviteAveryWorkPending000000000001');
            }

            if ($averyHome instanceof Project) {
                $this->upsertInvitation($averyHome, $avery, ProjectRole::Viewer, 'RufloDemoInviteAveryHomeCancelled0000000001', cancelled: true);
            }

            if ($morganWork instanceof Project) {
                $this->upsertInvitation($morganWork, $morgan, ProjectRole::Editor, 'RufloDemoInviteMorganWorkExpired0000000001', expired: true);
                $this->upsertInvitation($morganWork, $morgan, ProjectRole::Viewer, 'RufloDemoInviteMorganWorkAccepted000000001', acceptedBy: $avery);
            }
        });
    }

    private function project(User $owner, string $name): ?Project
    {
        return Project::query()
            ->where('user_id', $owner->id)
            ->where('name', $name)
            ->first();
    }

    private function upsertInvitation(
        Project $project,
        User $invitedBy,
        ProjectRole $role,
        string $token,
        bool $cancelled = false,
        bool $expired = false,
        ?User $acceptedBy = null,
    ): void {
        ProjectInvitation::query()->updateOrCreate(
            [
                'token_hash' => ProjectInvitation::hashToken($token),
            ],
            [
                'project_id' => $project->id,
                'invited_by_user_id' => $invitedBy->id,
                'accepted_by_user_id' => $acceptedBy?->id,
                'role' => $role,
                'token' => $token,
                'expires_at' => $expired ? now()->subDay() : now()->addDays(14),
                'cancelled_at' => $cancelled ? now()->subHour() : null,
                'accepted_at' => $acceptedBy instanceof User ? now()->subHour() : null,
            ],
        );
    }
}
