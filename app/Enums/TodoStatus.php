<?php

namespace App\Enums;

use App\Models\Todo;

/**
 * The lifecycle bucket a todo is currently in, for display and filtering.
 *
 * This is a derived view of the underlying columns, not a stored field. The
 * authoritative state lives in `is_completed`, `archived_at`, and `deleted_at`;
 * {@see Todo::status()} maps those to one of these cases. Trash takes
 * precedence over archived/completed because soft-deleted tasks are no longer
 * actionable in the main workspace.
 */
enum TodoStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Archived = 'archived';
    case Trash = 'trash';

    /**
     * Translatable label for the status.
     */
    public function label(): string
    {
        return __('todos.status.'.$this->value);
    }

    /**
     * Flux badge color for the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'blue',
            self::Completed => 'green',
            self::Archived => 'zinc',
            self::Trash => 'red',
        };
    }

    /**
     * The filterable tab values a user can switch between.
     *
     * @return list<string>
     */
    public static function tabValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
