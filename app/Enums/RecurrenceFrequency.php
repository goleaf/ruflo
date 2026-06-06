<?php

namespace App\Enums;

enum RecurrenceFrequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return __('todos.recurrence.frequency.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Daily => 'blue',
            self::Weekly => 'purple',
            self::Monthly => 'amber',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Daily => 'sun',
            self::Weekly => 'calendar-days',
            self::Monthly => 'calendar',
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
