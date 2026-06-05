<?php

namespace App\Actions\Todos;

use App\Data\Todos\TodoData;
use App\Events\TodoCreated;
use App\Models\Todo;
use App\Models\User;

final class CreateTodo
{
    public function handle(User $user, TodoData $data): Todo
    {
        $todo = $user->todos()->create([
            'title' => $data->title,
        ]);

        TodoCreated::dispatch($todo);

        return $todo;
    }
}
