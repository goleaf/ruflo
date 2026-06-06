<?php

namespace App\Enums;

enum RecurrenceEditScope: string
{
    case Occurrence = 'occurrence';
    case Series = 'series';

    public function label(): string
    {
        return __('todos.recurrence.edit_scope.scopes.'.$this->value.'.label');
    }

    public function description(): string
    {
        return __('todos.recurrence.edit_scope.scopes.'.$this->value.'.description');
    }

    public function icon(): string
    {
        return match ($this) {
            self::Occurrence => 'pencil-square',
            self::Series => 'rectangle-stack',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $scope): string => $scope->value,
            self::cases(),
        );
    }
}
