<?php

namespace App\Enums;

enum ProjectRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function label(): string
    {
        return __('todos.collaboration.roles.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Owner => 'blue',
            self::Manager => 'purple',
            self::Editor => 'amber',
            self::Viewer => 'zinc',
        };
    }

    public function canEditTasks(): bool
    {
        return in_array($this, [self::Owner, self::Manager, self::Editor], true);
    }

    public function canUpdateProject(): bool
    {
        return in_array($this, [self::Owner, self::Manager], true);
    }

    public function canManageProject(): bool
    {
        return $this === self::Owner;
    }

    public function canManageTasks(): bool
    {
        return in_array($this, [self::Owner, self::Manager], true);
    }

    public function canManageMembers(): bool
    {
        return in_array($this, [self::Owner, self::Manager], true);
    }

    /**
     * @return list<string>
     */
    public static function assignableValues(): array
    {
        return [
            self::Manager->value,
            self::Editor->value,
            self::Viewer->value,
        ];
    }
}
