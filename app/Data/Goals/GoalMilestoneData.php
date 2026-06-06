<?php

namespace App\Data\Goals;

use Illuminate\Support\Str;

final readonly class GoalMilestoneData
{
    public function __construct(
        public int $goalId,
        public string $title,
        public ?string $targetDate,
    ) {}

    /**
     * @param  array{goal_id: int|string, title: string, target_date?: string|null}  $validated
     */
    public static function fromArray(array $validated): self
    {
        return new self(
            goalId: (int) $validated['goal_id'],
            title: Str::of($validated['title'])->squish()->value(),
            targetDate: $validated['target_date'] ?? null,
        );
    }
}
