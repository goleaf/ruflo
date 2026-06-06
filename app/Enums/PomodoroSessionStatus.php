<?php

namespace App\Enums;

enum PomodoroSessionStatus: string
{
    case Running = 'running';
    case Paused = 'paused';
    case Completed = 'completed';
    case Abandoned = 'abandoned';

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return [
            self::Running->value,
            self::Paused->value,
        ];
    }
}
