<?php

namespace App\Actions\Todos;

use App\Enums\TodoStatus;
use App\Enums\TodoTransition;
use App\Exceptions\InvalidTodoTransition;
use App\Models\Todo;

final class TodoLifecycleStateMachine
{
    /**
     * Determine whether a transition is accepted from the task's current state.
     */
    public function can(Todo $todo, TodoTransition $transition): bool
    {
        return in_array($todo->status(), $transition->acceptedStatuses(), true);
    }

    /**
     * Enforce a transition before an action mutates lifecycle columns.
     *
     * Idempotent no-op states are accepted so duplicate clicks and direct
     * action calls can return cleanly without duplicate events.
     */
    public function assertCan(Todo $todo, TodoTransition $transition): void
    {
        if ($this->can($todo, $transition)) {
            return;
        }

        throw $this->exceptionFor($todo->status(), $transition);
    }

    /**
     * The expected display bucket after a successful transition.
     */
    public function targetStatus(Todo $todo, TodoTransition $transition): TodoStatus
    {
        return match ($transition) {
            TodoTransition::Complete => TodoStatus::Completed,
            TodoTransition::Reopen => TodoStatus::Active,
            TodoTransition::Archive => TodoStatus::Archived,
            TodoTransition::Unarchive => $todo->is_completed ? TodoStatus::Completed : TodoStatus::Active,
            TodoTransition::RestoreDeleted => match (true) {
                $todo->isArchived() => TodoStatus::Archived,
                $todo->is_completed => TodoStatus::Completed,
                default => TodoStatus::Active,
            },
            TodoTransition::Delete => TodoStatus::Trash,
            TodoTransition::Update => $todo->status(),
        };
    }

    private function exceptionFor(TodoStatus $status, TodoTransition $transition): InvalidTodoTransition
    {
        return match ($status) {
            TodoStatus::Archived => match ($transition) {
                TodoTransition::Complete => InvalidTodoTransition::cannotCompleteArchived(),
                TodoTransition::Reopen => InvalidTodoTransition::cannotReopenArchived(),
                TodoTransition::Update => InvalidTodoTransition::cannotEditArchived(),
                default => InvalidTodoTransition::invalid($status, $transition),
            },
            TodoStatus::Trash => match ($transition) {
                TodoTransition::Complete => InvalidTodoTransition::cannotCompleteTrashed(),
                TodoTransition::Reopen => InvalidTodoTransition::cannotReopenTrashed(),
                TodoTransition::Archive => InvalidTodoTransition::cannotArchiveTrashed(),
                TodoTransition::Unarchive => InvalidTodoTransition::cannotUnarchiveTrashed(),
                TodoTransition::Update => InvalidTodoTransition::cannotEditTrashed(),
                default => InvalidTodoTransition::invalid($status, $transition),
            },
            default => InvalidTodoTransition::invalid($status, $transition),
        };
    }
}
