<?php

namespace App\Enums;

enum TodoTransition: string
{
    case Complete = 'complete';
    case Reopen = 'reopen';
    case Archive = 'archive';
    case Unarchive = 'unarchive';
    case Delete = 'delete';
    case RestoreDeleted = 'restore_deleted';
    case Update = 'update';

    /**
     * States where the transition is accepted, including idempotent no-ops.
     *
     * @return list<TodoStatus>
     */
    public function acceptedStatuses(): array
    {
        return match ($this) {
            self::Complete,
            self::Reopen => [TodoStatus::Active, TodoStatus::Completed],
            self::Archive,
            self::Unarchive => [TodoStatus::Active, TodoStatus::Completed, TodoStatus::Archived],
            self::Delete,
            self::RestoreDeleted => TodoStatus::cases(),
            self::Update => [TodoStatus::Active, TodoStatus::Completed],
        };
    }
}
