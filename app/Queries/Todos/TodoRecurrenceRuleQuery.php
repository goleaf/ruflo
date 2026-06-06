<?php

namespace App\Queries\Todos;

use App\Models\Todo;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class TodoRecurrenceRuleQuery
{
    /**
     * @return Collection<int, TodoRecurrenceRule>
     */
    public function for(User $user, int $limit = 100): Collection
    {
        return TodoRecurrenceRule::query()
            ->ownedBy($user)
            ->withCount(['occurrences', 'exceptions'])
            ->with([
                'exceptions.todo',
                'occurrences' => fn ($query) => $query
                    ->with('recurrenceException')
                    ->where('todos.user_id', $user->id)
                    ->orderByDesc('recurrence_occurs_on')
                    ->orderByDesc('id'),
                'todo' => fn ($query) => $query->where('todos.user_id', $user->id),
            ])
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
            ->withCount(['occurrences', 'exceptions'])
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

    public function findGeneratedOccurrenceFor(User $user, int $occurrenceId): Todo
    {
        $occurrence = Todo::query()
            ->ownedBy($user)
            ->whereKey($occurrenceId)
            ->whereNotNull('recurrence_rule_id')
            ->whereNotNull('recurrence_source_todo_id')
            ->whereNotNull('recurrence_occurs_on')
            ->first();

        if ($occurrence instanceof Todo) {
            return $occurrence;
        }

        throw ValidationException::withMessages([
            'recurrenceOccurrence' => __('todos.recurrence.exceptions.validation.generated_occurrence'),
        ]);
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
