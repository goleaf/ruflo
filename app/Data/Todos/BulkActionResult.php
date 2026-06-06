<?php

namespace App\Data\Todos;

use Illuminate\Support\Collection;

final readonly class BulkActionResult
{
    /**
     * @param  list<int>  $ids
     */
    public static function fromIds(array $ids, int $affected, int $failed = 0): self
    {
        $selected = Collection::make($ids)
            ->map(fn (int $id): int => $id)
            ->unique()
            ->count();

        return new self(
            selected: $selected,
            affected: $affected,
            skipped: max(0, $selected - $affected - $failed),
            failed: $failed,
        );
    }

    public function __construct(
        public int $selected,
        public int $affected,
        public int $skipped = 0,
        public int $failed = 0,
    ) {}

    public function hasOutcome(): bool
    {
        return $this->selected > 0 || $this->affected > 0 || $this->skipped > 0 || $this->failed > 0;
    }
}
