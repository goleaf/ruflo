<?php

namespace App\Actions\Todos;

use App\Enums\TodoTransition;
use App\Events\TodoCompleted;
use App\Models\Todo;

/**
 * Completes an active task.
 *
 * Completion is reversible and is never deletion. Archived tasks must be
 * unarchived before completion. Completing an already-completed task is a no-op
 * so duplicate clicks do not emit duplicate lifecycle events.
 */
final class CompleteTodo
{
    public function __construct(
        private readonly TodoLifecycleStateMachine $stateMachine,
    ) {}

    public function handle(Todo $todo): Todo
    {
        $this->stateMachine->assertCan($todo, TodoTransition::Complete);

        if ($todo->is_completed) {
            return $todo;
        }

        $todo->is_completed = true;
        $todo->save();

        TodoCompleted::dispatch($todo);

        return $todo;
    }
}
