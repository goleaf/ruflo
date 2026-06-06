<?php

namespace App\Enums;

use InvalidArgumentException;

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
     * SQL CASE expression for priority sorting.
     */
    public static function sortCaseSql(string $column = 'priority'): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $column) !== 1) {
            throw new InvalidArgumentException('Priority sort column must be a trusted SQL identifier.');
        }

        $clauses = array_map(
            fn (self $priority): string => "when '{$priority->value}' then {$priority->weight()}",
            self::cases(),
        );

        return 'case '.$column.' '.implode(' ', $clauses).' else '.self::Normal->weight().' end';
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
