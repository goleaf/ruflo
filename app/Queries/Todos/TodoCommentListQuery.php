<?php

namespace App\Queries\Todos;

use App\Models\Todo;
use App\Models\TodoComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

final class TodoCommentListQuery
{
    /**
     * @return Builder<TodoComment>
     */
    public function forTodo(User $viewer, Todo $todo): Builder
    {
        Gate::forUser($viewer)->authorize('view', $todo);

        return TodoComment::query()
            ->withTrashed()
            ->with(['author:id,name,email', 'mentions.mentionedUser:id,name,email'])
            ->where('todo_id', $todo->id)
            ->where('user_id', $todo->user_id)
            ->oldest('created_at')
            ->oldest('id');
    }

    /**
     * @return Collection<int, TodoComment>
     */
    public function recentForTodo(User $viewer, Todo $todo, int $limit): Collection
    {
        return $this->forTodo($viewer, $todo)
            ->limit($this->normalizeLimit($limit))
            ->get();
    }

    public function countForTodo(User $viewer, Todo $todo): int
    {
        return (int) $this->forTodo($viewer, $todo)->count();
    }

    public function hasMoreThanForTodo(User $viewer, Todo $todo, int $limit): bool
    {
        return $this->forTodo($viewer, $todo)
            ->skip($this->normalizeLimit($limit))
            ->take(1)
            ->exists();
    }

    public function findFor(User $viewer, Todo $todo, int $commentId): TodoComment
    {
        return $this->forTodo($viewer, $todo)
            ->whereKey($commentId)
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    private function normalizeLimit(int $limit): int
    {
        return max(1, min($limit, 80));
    }
}
