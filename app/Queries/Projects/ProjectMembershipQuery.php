<?php

namespace App\Queries\Projects;

use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ProjectMembershipQuery
{
    /**
     * @return Builder<ProjectMembership>
     */
    public function activeForProject(Project $project): Builder
    {
        return ProjectMembership::query()
            ->with(['user:id,name,email'])
            ->where('project_id', $project->id)
            ->active()
            ->orderBy('role')
            ->orderBy('created_at');
    }

    /**
     * @return Collection<int, ProjectMembership>
     */
    public function listForProject(Project $project): Collection
    {
        return $this->activeForProject($project)->get();
    }

    public function activeForMember(Project $project, User $member): ?ProjectMembership
    {
        return ProjectMembership::query()
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->active()
            ->first();
    }
}
