<?php

namespace App\Queries\Projects;

use App\Models\Project;
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
     * Active (non-archived) projects, for pickers and filters.
     *
     * @return Collection<int, Project>
     */
    public function activeFor(User $user): Collection
    {
        return $this->visibleFor($user)->active()->get();
    }

    public function findVisibleFor(User $user, int $projectId): Project
    {
        return Project::query()->ownedBy($user)->findOrFail($projectId);
    }
}
