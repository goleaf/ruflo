<?php

namespace App\Exceptions;

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
        return new self('Archived tasks must be restored before they can be completed.');
    }

    public static function cannotReopenArchived(): self
    {
        return new self('Archived tasks must be restored before they can be reopened.');
    }

    public static function cannotEditArchived(): self
    {
        return new self('Archived tasks must be restored before they can be edited.');
    }
}
