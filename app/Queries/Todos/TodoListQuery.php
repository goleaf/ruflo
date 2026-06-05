<?php

namespace App\Queries\Todos;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class TodoListQuery
{
    /**
     * @return Builder<Todo>
     */
    public function visibleFor(User $user): Builder
    {
        return Todo::query()
            ->select(['id', 'user_id', 'title', 'is_completed', 'created_at', 'updated_at'])
            ->whereBelongsTo($user)
            ->latest();
    }

    public function findVisibleFor(User $user, int $todoId): Todo
    {
        return $this->visibleFor($user)->findOrFail($todoId);
    }

    /**
     * @return array{remaining: int, completed: int}
     */
    public function summaryFor(User $user): array
    {
        $summary = Todo::query()
            ->whereBelongsTo($user)
            ->selectRaw('sum(case when is_completed = 0 then 1 else 0 end) as remaining_count')
            ->selectRaw('sum(case when is_completed = 1 then 1 else 0 end) as completed_count')
            ->first();

        return [
            'remaining' => (int) $summary->remaining_count,
            'completed' => (int) $summary->completed_count,
        ];
    }
}
