<?php

namespace App\Enums;

enum AutomationRuleKind: string
{
    case PromoteOverdueTasks = 'promote_overdue_tasks';
    case ArchiveCompletedTasks = 'archive_completed_tasks';

    public function label(): string
    {
        return __('automation.kinds.'.$this->value.'.label');
    }

    public function description(): string
    {
        return __('automation.kinds.'.$this->value.'.description');
    }

    public function color(): string
    {
        return match ($this) {
            self::PromoteOverdueTasks => 'amber',
            self::ArchiveCompletedTasks => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PromoteOverdueTasks => 'arrow-up-circle',
            self::ArchiveCompletedTasks => 'archive-box',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultSettings(): array
    {
        return match ($this) {
            self::PromoteOverdueTasks => [],
            self::ArchiveCompletedTasks => ['days' => 7],
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
