<?php

namespace App\Queries\Todos;

use App\Models\Todo;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class TodoRecurrenceRuleQuery
{
    /**
     * @return Collection<int, TodoRecurrenceRule>
     */
    public function for(User $user, int $limit = 100): Collection
    {
        return TodoRecurrenceRule::query()
            ->ownedBy($user)
            ->with(['todo' => fn ($query) => $query->where('todos.user_id', $user->id)])
            ->orderByDesc('is_enabled')
            ->orderBy('starts_on')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, TodoRecurrenceRule>
     */
    public function activeFor(User $user, int $limit = 100): Collection
    {
        return TodoRecurrenceRule::query()
            ->ownedBy($user)
            ->where('is_enabled', true)
            ->with(['todo' => fn ($query) => $query->where('todos.user_id', $user->id)])
            ->orderBy('starts_on')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function findFor(User $user, int $ruleId): TodoRecurrenceRule
    {
        return TodoRecurrenceRule::query()
            ->ownedBy($user)
            ->with(['todo' => fn ($query) => $query->where('todos.user_id', $user->id)])
            ->findOrFail($ruleId);
    }

    public function forTodo(User $user, Todo $todo): ?TodoRecurrenceRule
    {
        return TodoRecurrenceRule::query()
            ->ownedBy($user)
            ->whereBelongsTo($todo)
            ->first();
    }

    public function findEligibleTaskFor(User $user, int $todoId): Todo
    {
        return Todo::query()
            ->ownedBy($user)
            ->active()
            ->findOrFail($todoId);
    }

    /**
     * @return Collection<int, Todo>
     */
    public function taskOptionsFor(User $user, int $limit = 150): Collection
    {
        return Todo::query()
            ->ownedBy($user)
            ->active()
            ->whereDoesntHave('recurrenceRule')
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderBy('title')
            ->limit($limit)
            ->get();
    }

    public function findForTodo(User $user, Todo $todo, int $ruleId): TodoRecurrenceRule
    {
        $rule = TodoRecurrenceRule::query()
            ->ownedBy($user)
            ->whereBelongsTo($todo)
            ->find($ruleId);

        if ($rule instanceof TodoRecurrenceRule) {
            return $rule;
        }

        throw (new ModelNotFoundException)->setModel(TodoRecurrenceRule::class, [$ruleId]);
    }
}
