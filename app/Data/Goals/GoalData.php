<?php

namespace App\Data\Goals;

use Illuminate\Support\Str;

final readonly class GoalData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public ?int $projectId,
        public ?string $targetDate,
    ) {}

    /**
     * @param  array{title: string, description?: string|null, project_id?: int|string|null, target_date?: string|null}  $validated
     */
    public static function fromArray(array $validated): self
    {
        $description = Str::of((string) ($validated['description'] ?? ''))->trim()->value();
        $projectId = $validated['project_id'] ?? null;

        return new self(
            title: Str::of($validated['title'])->squish()->value(),
            description: $description === '' ? null : $description,
            projectId: $projectId === null || $projectId === '' ? null : (int) $projectId,
            targetDate: $validated['target_date'] ?? null,
        );
    }

    /**
     * @return array{title: string, description: ?string, project_id: ?int, target_date: ?string}
     */
    public function toAttributes(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'project_id' => $this->projectId,
            'target_date' => $this->targetDate,
        ];
    }
}
