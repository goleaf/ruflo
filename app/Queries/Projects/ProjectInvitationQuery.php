<?php

namespace App\Queries\Projects;

use App\Models\Project;
use App\Models\ProjectInvitation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ProjectInvitationQuery
{
    /**
     * @return Builder<ProjectInvitation>
     */
    public function forProject(Project $project): Builder
    {
        return ProjectInvitation::query()
            ->with(['invitedBy:id,name,email', 'acceptedBy:id,name,email'])
            ->where('project_id', $project->id)
            ->latest();
    }

    /**
     * @return Collection<int, ProjectInvitation>
     */
    public function listForProject(Project $project): Collection
    {
        return $this->forProject($project)->limit(12)->get();
    }

    public function findForProject(Project $project, int $invitationId): ProjectInvitation
    {
        return ProjectInvitation::query()
            ->where('project_id', $project->id)
            ->findOrFail($invitationId);
    }

    public function findByToken(string $token): ProjectInvitation
    {
        return ProjectInvitation::query()
            ->with(['project:id,user_id,name,archived_at', 'invitedBy:id,name,email'])
            ->where('token_hash', ProjectInvitation::hashToken($token))
            ->firstOrFail();
    }
}
