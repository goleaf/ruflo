<?php

namespace App\Queries\Todos;

use App\Enums\Priority;
use App\Enums\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class TodoBoardQuery
{
    /**
     * @return array<string, array{status: TodoStatus, todos: Collection<int, Todo>}>
     */
    public function columnsFor(User $user): array
    {
        return [
            TodoStatus::Active->value => [
                'status' => TodoStatus::Active,
                'todos' => $this->columnFor($user, TodoStatus::Active),
            ],
            TodoStatus::Completed->value => [
                'status' => TodoStatus::Completed,
                'todos' => $this->columnFor($user, TodoStatus::Completed),
            ],
            TodoStatus::Archived->value => [
                'status' => TodoStatus::Archived,
                'todos' => $this->columnFor($user, TodoStatus::Archived),
            ],
        ];
    }

    /**
     * @return Collection<int, Todo>
     */
    private function columnFor(User $user, TodoStatus $status): Collection
    {
        $query = Todo::query()
            ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->with([
                'project' => fn (BelongsTo $project): BelongsTo => $project
                    ->where('user_id', $user->id)
                    ->select(['id', 'user_id', 'name', 'color', 'archived_at']),
                'tags' => fn (BelongsToMany $tags): BelongsToMany => $tags
                    ->where('tags.user_id', $user->id)
                    ->select(['tags.id', 'tags.user_id', 'tags.name', 'tags.color']),
            ]);

        $this->applyStatus($query, $status);

        return $query
            ->orderByRaw(Priority::sortCaseSql().' desc')
            ->orderByDesc('due_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(25)
            ->get();
    }

    /**
     * @param  Builder<Todo>  $query
     */
    private function applyStatus(Builder $query, TodoStatus $status): void
    {
        match ($status) {
            TodoStatus::Active => $query->active(),
            TodoStatus::Completed => $query->completed(),
            TodoStatus::Archived => $query->archived(),
            TodoStatus::Trash => $query->onlyTrashed(),
        };
    }
}
