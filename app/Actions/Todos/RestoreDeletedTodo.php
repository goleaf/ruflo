<?php

namespace App\Actions\Todos;

use App\Enums\TodoTransition;
use App\Events\TodoRestoredFromTrash;
use App\Models\Todo;

final class RestoreDeletedTodo
{
    public function __construct(
        private readonly TodoLifecycleStateMachine $stateMachine,
    ) {}

    public function handle(Todo $todo): void
    {
        $this->stateMachine->assertCan($todo, TodoTransition::RestoreDeleted);

        if (! $todo->trashed()) {
            return;
        }

        $todo->restore();

        TodoRestoredFromTrash::dispatch($todo);
    }
}
