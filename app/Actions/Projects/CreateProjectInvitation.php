<?php

namespace App\Actions\Projects;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Rules\Projects\ProjectInvitationExpiryDays;
use App\Rules\Projects\ProjectInviteRole;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateProjectInvitation
{
    public function handle(User $actor, Project $project, ProjectRole $role, int $expiresInDays): ProjectInvitation
    {
        Gate::forUser($actor)->authorize('manageMembers', $project);

        if ($project->isArchived()) {
            throw ValidationException::withMessages([
                'project' => __('todos.collaboration.invites.validation.project_active'),
            ]);
        }

        if (! ProjectInviteRole::isValid($role->value)) {
            throw ValidationException::withMessages([
                'inviteRole' => __('todos.collaboration.invites.validation.role'),
            ]);
        }

        if (ProjectInvitationExpiryDays::normalize($expiresInDays) === null) {
            throw ValidationException::withMessages([
                'inviteExpiresInDays' => __('todos.collaboration.invites.validation.expires_in_days'),
            ]);
        }

        $token = $this->uniqueToken();

        return ProjectInvitation::query()->create([
            'project_id' => $project->id,
            'invited_by_user_id' => $actor->id,
            'role' => $role,
            'token' => $token,
            'token_hash' => ProjectInvitation::hashToken($token),
            'expires_at' => now()->addDays($expiresInDays),
        ]);
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(48);
        } while (ProjectInvitation::query()->where('token_hash', ProjectInvitation::hashToken($token))->exists());

        return $token;
    }
}
