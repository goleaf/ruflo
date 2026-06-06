<?php

namespace App\Actions\Todos;

use App\Enums\TodoTransition;
use App\Events\TodoUpdated;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Moves a focused task's due date without changing its owner or lifecycle.
 */
final class RescheduleFocusedTodo
{
    public function __construct(
        private readonly TodoLifecycleStateMachine $stateMachine,
    ) {}

    public function defer(User $user, Todo $todo): Todo
    {
        return $this->moveTo($user, $todo, today()->addDay()->toDateString());
    }

    public function snooze(User $user, Todo $todo): Todo
    {
        return $this->moveTo($user, $todo, today()->addDays(3)->toDateString());
    }

    private function moveTo(User $user, Todo $todo, string $dueDate): Todo
    {
        Gate::forUser($user)->authorize('update', $todo);
        $this->stateMachine->assertCan($todo, TodoTransition::Update);

        $todo->forceFill([
            'due_date' => $dueDate,
        ])->save();

        TodoUpdated::dispatch($todo);

        return $todo->refresh();
    }
}
