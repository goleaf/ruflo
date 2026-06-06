<?php

namespace App\Actions\Todos;

use App\Models\Project;
use App\Models\User;
use App\Support\Projects\ProjectAccess;

/**
 * Ownership-safe resolution of a task's project and tags.
 *
 * The UI only ever offers a user their own projects/tags, but the backend must
 * not rely on that: these helpers re-scope every incoming id to the user so a
 * forged request silently drops foreign references instead of leaking or
 * cross-linking another workspace's data.
 */
trait ResolvesTodoOrganization
{
    /**
     * Return the project id only if the user owns it or can edit the shared project.
     */
    protected function resolveProjectId(User $user, ?int $projectId): ?int
    {
        if ($projectId === null) {
            return null;
        }

        $project = Project::query()
            ->select(['id', 'user_id'])
            ->find($projectId);

        if (! $project instanceof Project) {
            return null;
        }

        $role = app(ProjectAccess::class)->roleFor($user, $project);

        return $role?->canEditTasks() === true ? $project->id : null;
    }

    /**
     * Narrow the requested tag ids to those the user actually owns.
     *
     * @param  list<int>  $tagIds
     * @return list<int>
     */
    protected function resolveTagIds(User $user, array $tagIds): array
    {
        if ($tagIds === []) {
            return [];
        }

        return $user->tags()->whereKey($tagIds)->pluck('id')->all();
    }
}
