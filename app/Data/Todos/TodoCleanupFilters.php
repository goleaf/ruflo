<?php

namespace App\Data\Todos;

final readonly class TodoCleanupFilters
{
    public const string Stale = 'stale';

    public const string Unplanned = 'unplanned';

    public const string Blocked = 'blocked';

    public const string Risky = 'risky';

    public const string RiskSort = 'risk';

    public const string UpdatedSort = 'updated';

    public const string DueSort = 'due';

    public const string PrioritySort = 'priority';

    public const string TitleSort = 'title';

    public function __construct(
        public string $view = self::Stale,
        public ?string $search = null,
        public string $sort = self::RiskSort,
        public string $direction = 'desc',
        public bool $hasInvalidFilter = false,
    ) {}

    /**
     * @return list<string>
     */
    public static function viewOptions(): array
    {
        return [
            self::Stale,
            self::Unplanned,
            self::Blocked,
            self::Risky,
        ];
    }

    /**
     * @return list<string>
     */
    public static function sortOptions(): array
    {
        return [
            self::RiskSort,
            self::UpdatedSort,
            self::DueSort,
            self::PrioritySort,
            self::TitleSort,
        ];
    }

    public static function isValidView(string $view): bool
    {
        return in_array($view, self::viewOptions(), true);
    }

    public static function isValidSort(string $sort): bool
    {
        return in_array($sort, self::sortOptions(), true);
    }
}
