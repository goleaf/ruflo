<?php

namespace App\Enums;

/**
 * Task priority. Deliberately small and clear: most people need
 * low/normal/high plus an "urgent" escape hatch, not a spaceship cockpit.
 */
enum Priority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    /**
     * Translatable label for display.
     */
    public function label(): string
    {
        return __('todos.priority.'.$this->value);
    }

    /**
     * Flux badge color for the priority.
     */
    public function color(): string
    {
        return match ($this) {
            self::Low => 'zinc',
            self::Normal => 'blue',
            self::High => 'amber',
            self::Urgent => 'red',
        };
    }

    /**
     * Sort weight (higher = more important) for "priority" ordering.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Low => 0,
            self::Normal => 1,
            self::High => 2,
            self::Urgent => 3,
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
