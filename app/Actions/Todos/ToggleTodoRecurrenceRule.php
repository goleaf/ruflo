<?php

namespace App\Actions\Todos;

use App\Models\Todo;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ToggleTodoRecurrenceRule
{
    public function handle(User $user, TodoRecurrenceRule $rule): TodoRecurrenceRule
    {
        Gate::forUser($user)->authorize('update', $rule);

        $todo = $rule->todo;
        $enabling = ! $rule->is_enabled;

        if ($enabling && (! $todo instanceof Todo || ! $todo->isOwnedBy($user) || ! $todo->isActive() || $todo->trashed())) {
            throw ValidationException::withMessages([
                'recurrenceRule' => __('todos.validation.recurrence_task_actionable'),
            ]);
        }

        $rule->forceFill([
            'is_enabled' => $enabling,
        ])->save();

        return $rule->refresh()->load('todo');
    }
}
