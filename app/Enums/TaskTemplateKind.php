<?php

namespace App\Enums;

enum TaskTemplateKind: string
{
    case Task = 'task';
    case Project = 'project';
    case Checklist = 'checklist';
    case Routine = 'routine';

    public function label(): string
    {
        return __('todos.templates.kinds.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Task => 'blue',
            self::Project => 'green',
            self::Checklist => 'amber',
            self::Routine => 'purple',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Task => 'check-circle',
            self::Project => 'folder',
            self::Checklist => 'list-bullet',
            self::Routine => 'arrow-path',
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
