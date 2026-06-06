<?php

namespace App\Support\Projects;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;

final class ProjectAccess
{
    public function roleFor(User $user, Project $project): ?ProjectRole
    {
        if ($project->isOwnedBy($user)) {
            return ProjectRole::Owner;
        }

        $role = ProjectMembership::query()
            ->active()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->value('role');

        if ($role instanceof ProjectRole) {
            return $role;
        }

        return is_string($role) ? ProjectRole::tryFrom($role) : null;
    }

    public function roleForProjectId(User $user, int $projectId): ?ProjectRole
    {
        $project = Project::query()
            ->select(['id', 'user_id'])
            ->find($projectId);

        if (! $project instanceof Project) {
            return null;
        }

        return $this->roleFor($user, $project);
    }

    public function canView(User $user, Project $project): bool
    {
        return $this->roleFor($user, $project) !== null;
    }

    public function canEditTasks(User $user, Project $project): bool
    {
        return $this->roleFor($user, $project)?->canEditTasks() ?? false;
    }

    public function canUpdateProject(User $user, Project $project): bool
    {
        return $this->roleFor($user, $project)?->canUpdateProject() ?? false;
    }

    public function canManageProject(User $user, Project $project): bool
    {
        return $this->roleFor($user, $project)?->canManageProject() ?? false;
    }

    public function canManageTasks(User $user, Project $project): bool
    {
        return $this->roleFor($user, $project)?->canManageTasks() ?? false;
    }

    public function canManageMembers(User $user, Project $project): bool
    {
        return $this->roleFor($user, $project)?->canManageMembers() ?? false;
    }
}
