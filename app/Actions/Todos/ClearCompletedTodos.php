<?php

namespace App\Actions\Todos;

use App\Events\CompletedTodosCleared;
use App\Models\User;

final class ClearCompletedTodos
{
    public function handle(User $user): int
    {
        $deletedCount = $user->todos()
            ->where('is_completed', true)
            ->delete();

        if ($deletedCount > 0) {
            CompletedTodosCleared::dispatch($user, $deletedCount);
        }

        return $deletedCount;
    }
}
