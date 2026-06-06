<?php

namespace App\Queries\Todos;

use App\Models\PomodoroSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class PomodoroSessionQuery
{
    public function activeFor(User $user): ?PomodoroSession
    {
        return PomodoroSession::query()
            ->ownedBy($user)
            ->active()
            ->with('todo')
            ->latest('updated_at')
            ->latest('id')
            ->first();
    }

    public function activeForTodo(User $user, int $todoId): ?PomodoroSession
    {
        return PomodoroSession::query()
            ->ownedBy($user)
            ->active()
            ->where('todo_id', $todoId)
            ->with('todo')
            ->latest('updated_at')
            ->latest('id')
            ->first();
    }

    public function findActiveFor(User $user, int $sessionId): PomodoroSession
    {
        $session = PomodoroSession::query()
            ->ownedBy($user)
            ->active()
            ->with('todo')
            ->find($sessionId);

        if ($session instanceof PomodoroSession) {
            return $session;
        }

        throw (new ModelNotFoundException)->setModel(PomodoroSession::class, [$sessionId]);
    }

    /**
     * @return Collection<int, PomodoroSession>
     */
    public function recentFor(User $user, int $limit = 5): Collection
    {
        return PomodoroSession::query()
            ->ownedBy($user)
            ->with('todo')
            ->latest('updated_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
