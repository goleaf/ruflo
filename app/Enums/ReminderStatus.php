<?php

namespace App\Enums;

enum ReminderStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return __('reminders.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Processed => 'green',
            self::Skipped => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'bell',
            self::Processed => 'check-circle',
            self::Skipped => 'pause-circle',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
