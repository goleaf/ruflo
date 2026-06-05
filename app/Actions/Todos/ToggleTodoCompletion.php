<?php

namespace App\Actions\Todos;

use App\Events\TodoCompletionToggled;
use App\Models\Todo;

final class ToggleTodoCompletion
{
    public function handle(Todo $todo): Todo
    {
        $todo->update([
            'is_completed' => ! $todo->is_completed,
        ]);

        TodoCompletionToggled::dispatch($todo);

        return $todo;
    }
}
