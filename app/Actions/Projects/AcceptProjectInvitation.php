<?php

namespace App\Actions\Projects;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AcceptProjectInvitation
{
    public function __construct(
        private readonly AddProjectMember $members,
    ) {}

    public function handle(User $user, string $token): ProjectInvitation
    {
        return DB::transaction(function () use ($user, $token): ProjectInvitation {
            $invitation = ProjectInvitation::query()
                ->with(['project:id,user_id,name,archived_at', 'invitedBy:id,name,email'])
                ->where('token_hash', ProjectInvitation::hashToken($token))
                ->lockForUpdate()
                ->firstOrFail();

            if (! $invitation->isPending()) {
                throw ValidationException::withMessages([
                    'invitation' => __('todos.collaboration.invites.validation.accept_pending'),
                ]);
            }

            $project = $invitation->project;

            if (! $project instanceof Project || $project->isArchived()) {
                throw ValidationException::withMessages([
                    'invitation' => __('todos.collaboration.invites.validation.accept_unavailable'),
                ]);
            }

            if (! $invitation->invitedBy instanceof User) {
                throw ValidationException::withMessages([
                    'invitation' => __('todos.collaboration.invites.validation.accept_unavailable'),
                ]);
            }

            $role = $invitation->role;

            if (! $role instanceof ProjectRole || ! in_array($role->value, ProjectRole::assignableValues(), true)) {
                throw ValidationException::withMessages([
                    'invitation' => __('todos.collaboration.invites.validation.accept_role'),
                ]);
            }

            Gate::forUser($invitation->invitedBy)->authorize('manageMembers', $project);

            $this->members->handle($invitation->invitedBy, $project, $user, $role);

            $invitation->forceFill([
                'accepted_by_user_id' => $user->id,
                'accepted_at' => now(),
            ])->save();

            return $invitation;
        });
    }
}
