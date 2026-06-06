<?php

namespace App\Queries\Todos;

use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Owner-scoped read boundary for contained task checklist rows.
 */
class TodoChecklistItemListQuery
{
    /**
     * @return Collection<int, TodoChecklistItem>
     */
    public function forTodo(User $user, Todo $todo): Collection
    {
        return TodoChecklistItem::query()
            ->select(['id', 'user_id', 'todo_id', 'title', 'is_completed', 'completed_at', 'position', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->whereBelongsTo($todo)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public function findFor(User $user, Todo $todo, int $itemId): TodoChecklistItem
    {
        return TodoChecklistItem::query()
            ->ownedBy($user)
            ->whereBelongsTo($todo)
            ->findOrFail($itemId);
    }
}
