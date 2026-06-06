<?php

namespace App\Enums;

enum RecurrenceEndType: string
{
    case Never = 'never';
    case OnDate = 'on_date';
    case AfterOccurrences = 'after_occurrences';

    public function label(): string
    {
        return __('todos.recurrence.end_type.'.$this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
