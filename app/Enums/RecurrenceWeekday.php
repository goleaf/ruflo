<?php

namespace App\Enums;

enum RecurrenceWeekday: string
{
    case Monday = 'monday';
    case Tuesday = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday = 'thursday';
    case Friday = 'friday';
    case Saturday = 'saturday';
    case Sunday = 'sunday';

    public function label(): string
    {
        return __('todos.recurrence.weekdays.'.$this->value);
    }

    public function shortLabel(): string
    {
        return __('todos.recurrence.weekdays_short.'.$this->value);
    }

    public static function fromIsoWeekday(int $weekday): self
    {
        return match ($weekday) {
            1 => self::Monday,
            2 => self::Tuesday,
            3 => self::Wednesday,
            4 => self::Thursday,
            5 => self::Friday,
            6 => self::Saturday,
            7 => self::Sunday,
            default => self::Monday,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @param  list<string>  $weekdays
     * @return list<string>
     */
    public static function sortValues(array $weekdays): array
    {
        $allowed = self::values();

        return array_values(array_intersect($allowed, array_values(array_unique($weekdays))));
    }
}
