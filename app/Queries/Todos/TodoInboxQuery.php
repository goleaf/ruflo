<?php

namespace App\Queries\Todos;

use App\Enums\Priority;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Owner-scoped inbox read boundary for captured tasks that still need triage.
 */
final class TodoInboxQuery
{
    /**
     * @return Builder<Todo>
     */
    public function for(User $user): Builder
    {
        return $this->withWorkspaceRelations(
            Todo::query()
                ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'inbox_captured_at', 'created_at', 'updated_at'])
                ->ownedBy($user)
                ->active()
                ->inInbox(),
            $user,
        )
            ->orderByDesc('inbox_captured_at')
            ->orderByRaw(Priority::sortCaseSql().' desc')
            ->orderByDesc('id');
    }

    public function findFor(User $user, int $todoId): Todo
    {
        return $this->for($user)->findOrFail($todoId);
    }

    /**
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    private function withWorkspaceRelations(Builder $query, User $user): Builder
    {
        return $query->with([
            'project' => fn (BelongsTo $project): BelongsTo => $project->where('projects.user_id', $user->id),
            'tags' => fn (BelongsToMany $tags): BelongsToMany => $tags->where('tags.user_id', $user->id),
        ]);
    }
}
