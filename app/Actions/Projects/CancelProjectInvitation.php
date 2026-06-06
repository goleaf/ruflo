<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CancelProjectInvitation
{
    public function handle(User $actor, Project $project, ProjectInvitation $invitation): ProjectInvitation
    {
        Gate::forUser($actor)->authorize('manageMembers', $project);

        if ($invitation->project_id !== $project->id) {
            abort(404);
        }

        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => __('todos.collaboration.invites.validation.cancel_pending'),
            ]);
        }

        $invitation->forceFill([
            'cancelled_at' => now(),
        ])->save();

        return $invitation;
    }
}
