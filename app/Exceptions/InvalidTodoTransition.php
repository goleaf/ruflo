<?php

namespace App\Exceptions;

use App\Enums\TodoStatus;
use App\Enums\TodoTransition;
use RuntimeException;

/**
 * Raised when a lifecycle transition is requested that the state machine does
 * not allow (for example, completing a task that is archived). These are
 * programmer/guard errors surfaced as safe, translatable user messages — they
 * never leak private data.
 */
final class InvalidTodoTransition extends RuntimeException
{
    public static function cannotCompleteArchived(): self
    {
        return new self((string) __('todos.exceptions.cannot_complete_archived'));
    }

    public static function cannotReopenArchived(): self
    {
        return new self((string) __('todos.exceptions.cannot_reopen_archived'));
    }

    public static function cannotEditArchived(): self
    {
        return new self((string) __('todos.exceptions.cannot_edit_archived'));
    }

    public static function cannotCompleteTrashed(): self
    {
        return new self((string) __('todos.exceptions.cannot_complete_trashed'));
    }

    public static function cannotReopenTrashed(): self
    {
        return new self((string) __('todos.exceptions.cannot_reopen_trashed'));
    }

    public static function cannotArchiveTrashed(): self
    {
        return new self((string) __('todos.exceptions.cannot_archive_trashed'));
    }

    public static function cannotUnarchiveTrashed(): self
    {
        return new self((string) __('todos.exceptions.cannot_unarchive_trashed'));
    }

    public static function cannotEditTrashed(): self
    {
        return new self((string) __('todos.exceptions.cannot_edit_trashed'));
    }

    public static function invalid(TodoStatus $status, TodoTransition $transition): self
    {
        return new self((string) __('todos.exceptions.invalid_transition', [
            'status' => $status->value,
            'transition' => str_replace('_', ' ', $transition->value),
        ]));
    }
}
