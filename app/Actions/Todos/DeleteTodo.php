<?php

namespace App\Actions\Todos;

use App\Enums\TodoTransition;
use App\Events\TodoDeleted;
use App\Models\Todo;

final class DeleteTodo
{
    public function __construct(
        private readonly TodoLifecycleStateMachine $stateMachine,
    ) {}

    public function handle(Todo $todo): void
    {
        $this->stateMachine->assertCan($todo, TodoTransition::Delete);

        if ($todo->trashed()) {
            return;
        }

        $todo->delete();

        TodoDeleted::dispatch($todo);
    }
}
