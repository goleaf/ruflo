<?php

namespace App\Data\Habits;

use App\Enums\HabitFrequency;

final readonly class HabitData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public HabitFrequency $frequency,
        public int $targetCount,
        public ?int $goalId,
    ) {}

    /**
     * @param  array{title: string, description?: string|null, frequency: string, target_count: int|string, goal_id?: int|string|null}  $input
     */
    public static function fromArray(array $input): self
    {
        $frequency = HabitFrequency::from((string) $input['frequency']);

        return new self(
            title: trim($input['title']),
            description: filled($input['description'] ?? null) ? trim((string) $input['description']) : null,
            frequency: $frequency,
            targetCount: (int) $input['target_count'],
            goalId: filled($input['goal_id'] ?? null) ? (int) $input['goal_id'] : null,
        );
    }
}
