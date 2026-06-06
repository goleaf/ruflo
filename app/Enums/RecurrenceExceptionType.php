<?php

namespace App\Enums;

enum RecurrenceExceptionType: string
{
    case Skipped = 'skipped';
    case Moved = 'moved';
    case Edited = 'edited';

    public function label(): string
    {
        return __('todos.recurrence.exceptions.types.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Skipped => 'amber',
            self::Moved => 'blue',
            self::Edited => 'purple',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Skipped => 'no-symbol',
            self::Moved => 'arrow-right-circle',
            self::Edited => 'pencil-square',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            fn (self $type): string => $type->value,
            self::cases(),
        );
    }
}
