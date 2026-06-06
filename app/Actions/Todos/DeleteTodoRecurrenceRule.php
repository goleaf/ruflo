<?php

namespace App\Actions\Todos;

use App\Models\Todo;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use App\Queries\Todos\TodoRecurrenceRuleQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class DeleteTodoRecurrenceRule
{
    public function __construct(
        private readonly TodoRecurrenceRuleQuery $rules,
    ) {}

    public function handle(User $user, Todo|TodoRecurrenceRule $target): void
    {
        $rule = $target instanceof Todo
            ? $this->ruleFromTodo($user, $target)
            : $target;

        if (! $rule instanceof TodoRecurrenceRule) {
            return;
        }

        Gate::forUser($user)->authorize('delete', $rule);
        $this->assertRuleCanBeCleared($user, $rule);

        $rule->delete();
    }

    private function ruleFromTodo(User $user, Todo $todo): ?TodoRecurrenceRule
    {
        Gate::forUser($user)->authorize('update', $todo);

        return $this->rules->forTodo($user, $todo);
    }

    private function assertRuleCanBeCleared(User $user, TodoRecurrenceRule $rule): void
    {
        $todo = $rule->todo;

        if (! $todo instanceof Todo || ! $todo->isOwnedBy($user) || ! $todo->isActive() || $todo->trashed()) {
            throw ValidationException::withMessages([
                'recurrenceRule' => __('todos.validation.recurrence_task_actionable'),
            ]);
        }
    }
}
