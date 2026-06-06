<?php

namespace App\Actions\Todos;

use App\Events\TodoRestoredFromTrash;
use App\Models\Todo;

final class RestoreDeletedTodo
{
    public function handle(Todo $todo): void
    {
        if (! $todo->trashed()) {
            return;
        }

        $todo->restore();

        TodoRestoredFromTrash::dispatch($todo);
    }
}
