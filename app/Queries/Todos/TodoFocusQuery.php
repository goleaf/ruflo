<?php

namespace App\Queries\Todos;

use App\Enums\Priority;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Owner-scoped read boundary for the focused task set.
 *
 * Urgent tasks are always included, even if that makes the set larger than the
 * normal target size. The remaining slots are filled with overdue, due-today,
 * and high-priority active tasks.
 */
final class TodoFocusQuery
{
    public const TargetSize = 5;

    /**
     * @return Collection<int, Todo>
     */
    public function for(User $user): Collection
    {
        $urgent = $this->base($user)
            ->where('priority', Priority::Urgent->value)
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $remaining = max(0, self::TargetSize - $urgent->count());

        if ($remaining === 0) {
            return $urgent->values();
        }

        $today = today()->toDateString();

        $fill = $this->base($user)
            ->where('priority', '!=', Priority::Urgent->value)
            ->where(function (Builder $query) use ($today): void {
                $query
                    ->where('priority', Priority::High->value)
                    ->orWhereDate('due_date', '<=', $today);
            })
            ->orderByRaw(
                'case when due_date is not null and due_date < ? then 0 when due_date is not null and due_date = ? then 1 when priority = ? then 2 else 3 end',
                [$today, $today, Priority::High->value],
            )
            ->orderByRaw(Priority::sortCaseSql().' desc')
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($remaining)
            ->get();

        return $urgent->concat($fill)->values();
    }

    public function findFor(User $user, int $todoId): Todo
    {
        $todo = $this->for($user)->firstWhere('id', $todoId);

        if ($todo instanceof Todo) {
            return $todo;
        }

        throw (new ModelNotFoundException)->setModel(Todo::class, [$todoId]);
    }

    /**
     * @return Builder<Todo>
     */
    private function base(User $user): Builder
    {
        return Todo::query()
            ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'inbox_captured_at', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->active()
            ->with([
                'project' => fn (BelongsTo $project): BelongsTo => $project->where('projects.user_id', $user->id),
                'tags' => fn (BelongsToMany $tags): BelongsToMany => $tags->where('tags.user_id', $user->id),
            ]);
    }
}
