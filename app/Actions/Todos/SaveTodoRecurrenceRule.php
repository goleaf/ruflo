<?php

namespace App\Actions\Todos;

use App\Data\Todos\RecurrenceRuleData;
use App\Enums\TodoTransition;
use App\Models\Todo;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use App\Queries\Todos\TodoRecurrenceRuleQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SaveTodoRecurrenceRule
{
    public function __construct(
        private readonly TodoLifecycleStateMachine $stateMachine,
        private readonly TodoRecurrenceRuleQuery $rules,
    ) {}

    public function handle(User $user, Todo $todo, RecurrenceRuleData $data): TodoRecurrenceRule
    {
        Gate::forUser($user)->authorize('update', $todo);
        Gate::forUser($user)->authorize('create', TodoRecurrenceRule::class);
        $this->assertTaskCanCarryRule($user, $todo);
        $this->stateMachine->assertCan($todo, TodoTransition::Update);

        $rule = $this->rules->forTodo($user, $todo);

        if ($rule instanceof TodoRecurrenceRule) {
            Gate::forUser($user)->authorize('update', $rule);
        } else {
            $rule = $user->todoRecurrenceRules()->make();
            $rule->todo()->associate($todo);
        }

        $rule->forceFill($data->toAttributes())->save();

        return $rule->refresh()->load('todo');
    }

    private function assertTaskCanCarryRule(User $user, Todo $todo): void
    {
        if (! $todo->isOwnedBy($user) || ! $todo->isActive() || $todo->trashed()) {
            throw ValidationException::withMessages([
                'recurrenceRule' => __('todos.validation.recurrence_task_actionable'),
            ]);
        }
    }
}
