<?php

namespace App\Data\Reminders;

use App\Data\Processing\ManualWebProcessResult;
use Carbon\CarbonInterface;

final readonly class ReminderProcessingResult
{
    /**
     * @param  list<array{id: int, title: string, status: string, message: string}>  $details
     */
    public function __construct(
        public int $matchedCount,
        public int $processedCount,
        public int $skippedCount,
        public int $failedCount,
        public int $remainingCount,
        public array $details,
        public CarbonInterface $startedAt,
        public CarbonInterface $finishedAt,
    ) {}

    public function changedCount(): int
    {
        return $this->processedCount + $this->skippedCount;
    }

    public static function fromManualResult(
        ManualWebProcessResult $result,
        int $processedCount,
        int $skippedCount,
        int $failedCount,
    ): self {
        /** @var list<array{id: int, title: string, status: string, message: string}> $details */
        $details = $result->details;

        return new self(
            matchedCount: $result->matchedCount,
            processedCount: $processedCount,
            skippedCount: $skippedCount,
            failedCount: $failedCount,
            remainingCount: $result->skippedCount,
            details: $details,
            startedAt: $result->startedAt,
            finishedAt: $result->finishedAt,
        );
    }
}
