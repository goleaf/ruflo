<?php

namespace App\Enums;

enum AutomationRunStatus: string
{
    case Completed = 'completed';
    case Disabled = 'disabled';
    case Failed = 'failed';

    public function label(): string
    {
        return __('automation.run_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Completed => 'green',
            self::Disabled => 'zinc',
            self::Failed => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Completed => 'check-circle',
            self::Disabled => 'pause-circle',
            self::Failed => 'x-circle',
        };
    }
}
