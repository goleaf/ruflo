<?php

namespace App\Queries\Projects;

use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Owner-scoped read boundary for projects. Pickers and filters must source
 * their options here so one user's project names never appear for another.
 */
final class ProjectListQuery
{
    /**
     * @return Builder<Project>
     */
    public function visibleFor(User $user): Builder
    {
        return Project::query()->ownedBy($user)->orderBy('name');
    }

    /**
     * Projects the user can open: owned projects plus projects shared through
     * an active membership. Project management pickers intentionally keep using
     * `visibleFor()` until the member-management step widens those surfaces.
     *
     * @return Builder<Project>
     */
    public function accessibleFor(User $user): Builder
    {
        return Project::query()
            ->with('user:id,name,email')
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->ownedBy($user)
                    ->orWhereIn(
                        'id',
                        ProjectMembership::query()
                            ->active()
                            ->where('user_id', $user->id)
                            ->select('project_id'),
                    );
            })
            ->orderBy('name');
    }

    /**
     * Active (non-archived) projects, for pickers and filters.
     *
     * @return Collection<int, Project>
     */
    public function activeFor(User $user): Collection
    {
        return $this->visibleFor($user)->active()->get();
    }

    /**
     * Active projects the user can read in filter and dashboard scopes.
     *
     * @return Collection<int, Project>
     */
    public function activeAccessibleFor(User $user): Collection
    {
        return $this->accessibleFor($user)->active()->get();
    }

    public function activeAccessibleExists(User $user, int $projectId): bool
    {
        return $this->accessibleFor($user)
            ->active()
            ->whereKey($projectId)
            ->exists();
    }

    public function findVisibleFor(User $user, int $projectId): Project
    {
        return Project::query()->ownedBy($user)->findOrFail($projectId);
    }

    public function findAccessibleFor(User $user, int $projectId): Project
    {
        return $this->accessibleFor($user)
            ->with('user:id,name,email')
            ->findOrFail($projectId);
    }
}
