<?php

namespace App\Queries\Activity;

use App\Models\ActivityRecord;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ActivityFeedQuery
{
    /**
     * @return Builder<ActivityRecord>
     */
    public function for(User $user): Builder
    {
        return ActivityRecord::query()
            ->ownedBy($user)
            ->with(['actor:id,name'])
            ->latest('occurred_at')
            ->latest('id');
    }

    /**
     * @return Collection<int, ActivityRecord>
     */
    public function recentFor(User $user, int $limit): Collection
    {
        return $this->for($user)
            ->limit($this->normalizeLimit($limit))
            ->get();
    }

    public function hasMoreThan(User $user, int $limit): bool
    {
        return $this->for($user)
            ->skip($this->normalizeLimit($limit))
            ->take(1)
            ->exists();
    }

    /**
     * @return Builder<ActivityRecord>
     */
    public function forTodo(User $user, Todo $todo): Builder
    {
        return ActivityRecord::query()
            ->where('user_id', $todo->user_id)
            ->with(['actor:id,name'])
            ->latest('occurred_at')
            ->latest('id')
            ->where('subject_type', $todo->getMorphClass())
            ->where('subject_id', $todo->getKey());
    }

    /**
     * @return Collection<int, ActivityRecord>
     */
    public function recentForTodo(User $user, Todo $todo, int $limit): Collection
    {
        return $this->forTodo($user, $todo)
            ->limit($this->normalizeLimit($limit))
            ->get();
    }

    public function hasMoreThanForTodo(User $user, Todo $todo, int $limit): bool
    {
        return $this->forTodo($user, $todo)
            ->skip($this->normalizeLimit($limit))
            ->take(1)
            ->exists();
    }

    /**
     * @return array{total: int, today: int, tasks: int, checklist: int}
     */
    public function summaryFor(User $user): array
    {
        $base = ActivityRecord::query()->ownedBy($user);

        return [
            'total' => (int) (clone $base)->count(),
            'today' => (int) (clone $base)->whereDate('occurred_at', today())->count(),
            'tasks' => (int) (clone $base)->where('event', 'like', 'todo.%')->count(),
            'checklist' => (int) (clone $base)->where('event', 'like', 'todo.checklist_%')->count(),
        ];
    }

    /**
     * @param  list<int>  $todoIds
     * @return list<int>
     */
    public function visibleTodoIdsFor(User $user, array $todoIds): array
    {
        if ($todoIds === []) {
            return [];
        }

        return Todo::query()
            ->ownedBy($user)
            ->whereKey($todoIds)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    private function normalizeLimit(int $limit): int
    {
        return max(1, min($limit, 100));
    }
}
