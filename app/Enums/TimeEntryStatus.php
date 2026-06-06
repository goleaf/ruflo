<?php

namespace App\Enums;

enum TimeEntryStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Discarded = 'discarded';

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return [
            self::Running->value,
        ];
    }

    public function color(): string
    {
        return match ($this) {
            self::Running => 'green',
            self::Completed => 'blue',
            self::Discarded => 'zinc',
        };
    }
}
