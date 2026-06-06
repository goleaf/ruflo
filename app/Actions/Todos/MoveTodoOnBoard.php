<?php

namespace App\Actions\Todos;

use App\Enums\TodoStatus;
use App\Events\TodoUpdated;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class MoveTodoOnBoard
{
    public function __construct(
        private readonly CompleteTodo $completeTodo,
        private readonly ReopenTodo $reopenTodo,
        private readonly ArchiveTodo $archiveTodo,
        private readonly UnarchiveTodo $unarchiveTodo,
    ) {}

    public function handle(User $user, Todo $todo, TodoStatus $targetStatus, ?int $projectId = null): Todo
    {
        Gate::forUser($user)->authorize('update', $todo);

        if ($targetStatus === TodoStatus::Trash) {
            throw ValidationException::withMessages([
                'targetStatus' => __('todos.validation.board_status'),
            ]);
        }

        match ($targetStatus) {
            TodoStatus::Active => $this->moveToActive($user, $todo),
            TodoStatus::Completed => $this->moveToCompleted($user, $todo),
            TodoStatus::Archived => $this->moveToArchived($user, $todo),
            TodoStatus::Trash => null,
        };

        $todo->refresh();
        $resolvedProjectId = $this->resolveActiveProjectId($user, $projectId);

        if ($todo->project_id !== $resolvedProjectId) {
            $todo->project_id = $resolvedProjectId;
            $todo->save();

            TodoUpdated::dispatch($todo);
        }

        return $todo->refresh();
    }

    private function moveToActive(User $user, Todo $todo): void
    {
        if ($todo->isArchived()) {
            Gate::forUser($user)->authorize('unarchive', $todo);
            $this->unarchiveTodo->handle($todo);
            $todo->refresh();
        }

        if ($todo->is_completed) {
            Gate::forUser($user)->authorize('reopen', $todo);
            $this->reopenTodo->handle($todo);
        }
    }

    private function moveToCompleted(User $user, Todo $todo): void
    {
        if ($todo->isArchived()) {
            Gate::forUser($user)->authorize('unarchive', $todo);
            $this->unarchiveTodo->handle($todo);
            $todo->refresh();
        }

        if (! $todo->is_completed) {
            Gate::forUser($user)->authorize('complete', $todo);
            $this->completeTodo->handle($todo);
        }
    }

    private function moveToArchived(User $user, Todo $todo): void
    {
        if (! $todo->isArchived()) {
            Gate::forUser($user)->authorize('archive', $todo);
            $this->archiveTodo->handle($todo);
        }
    }

    private function resolveActiveProjectId(User $user, ?int $projectId): ?int
    {
        if ($projectId === null) {
            return null;
        }

        return $user->projects()->active()->whereKey($projectId)->value('id');
    }
}
