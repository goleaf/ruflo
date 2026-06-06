<?php

namespace App\Data\Processing;

use Carbon\CarbonInterface;

final readonly class ManualWebProcessResult
{
    /**
     * @param  list<array<string, mixed>>  $details
     */
    public function __construct(
        public string $processKey,
        public int $matchedCount,
        public int $processedCount,
        public int $changedCount,
        public int $skippedCount,
        public array $details,
        public CarbonInterface $startedAt,
        public CarbonInterface $finishedAt,
    ) {}

    public function hasRemaining(): bool
    {
        return $this->skippedCount > 0;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function detailsFor(string $key): array
    {
        return [
            $key => $this->details,
        ];
    }
}
