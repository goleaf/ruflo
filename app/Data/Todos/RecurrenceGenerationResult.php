<?php

namespace App\Data\Todos;

use App\Data\Processing\ManualWebProcessResult;
use Carbon\CarbonInterface;

final readonly class RecurrenceGenerationResult
{
    /**
     * @param  list<array<string, mixed>>  $details
     */
    public function __construct(
        public string $processKey,
        public int $matchedCount,
        public int $processedRuleCount,
        public int $createdCount,
        public int $skippedRuleCount,
        public int $failedCount,
        public int $remainingCount,
        public array $details,
        public CarbonInterface $windowEnd,
        public CarbonInterface $startedAt,
        public CarbonInterface $finishedAt,
    ) {}

    public static function fromManualResult(
        ManualWebProcessResult $result,
        int $createdCount,
        int $skippedRuleCount,
        int $failedCount,
        CarbonInterface $windowEnd,
    ): self {
        return new self(
            processKey: $result->processKey,
            matchedCount: $result->matchedCount,
            processedRuleCount: $result->processedCount,
            createdCount: $createdCount,
            skippedRuleCount: $skippedRuleCount,
            failedCount: $failedCount,
            remainingCount: $result->skippedCount,
            details: $result->details,
            windowEnd: $windowEnd,
            startedAt: $result->startedAt,
            finishedAt: $result->finishedAt,
        );
    }

    public function changedCount(): int
    {
        return $this->createdCount;
    }
}
