<?php

namespace App\Actions\Todos;

use App\Events\TodoDeleted;
use App\Models\Todo;

final class DeleteTodo
{
    public function handle(Todo $todo): void
    {
        if ($todo->trashed()) {
            return;
        }

        $todo->delete();

        TodoDeleted::dispatch($todo);
    }
}
