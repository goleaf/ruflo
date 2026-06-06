<?php

namespace App\Enums;

enum TimeEntrySource: string
{
    case Manual = 'manual';
    case Timer = 'timer';
    case Pomodoro = 'pomodoro';

    public function color(): string
    {
        return match ($this) {
            self::Manual => 'zinc',
            self::Timer => 'green',
            self::Pomodoro => 'purple',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Manual => 'pencil-square',
            self::Timer => 'clock',
            self::Pomodoro => 'bolt',
        };
    }
}
