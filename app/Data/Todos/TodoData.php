<?php

namespace App\Data\Todos;

use App\Enums\Priority;

/**
 * Validated, normalized input for creating or editing a task.
 *
 * This carries only what the user controls. Ownership-sensitive references
 * (`projectId`, `tagIds`) are still re-verified against the authenticated user
 * inside the action — a DTO is convenience, not trust.
 */
final readonly class TodoData
{
    /**
     * @param  list<int>  $tagIds
     */
    public function __construct(
        public string $title,
        public Priority $priority = Priority::Normal,
        public ?string $dueDate = null,
        public ?int $projectId = null,
        public array $tagIds = [],
    ) {
        //
    }

    /**
     * @param array{
     *     title: string,
     *     priority?: string|null,
     *     due_date?: string|null,
     *     project_id?: int|string|null,
     *     tag_ids?: array<int, int|string>|null
     * } $validated
     */
    public static function fromArray(array $validated): self
    {
        $dueDate = $validated['due_date'] ?? null;
        $projectId = $validated['project_id'] ?? null;

        return new self(
            title: trim($validated['title']),
            priority: Priority::tryFrom($validated['priority'] ?? '') ?? Priority::Normal,
            dueDate: ($dueDate === null || $dueDate === '') ? null : $dueDate,
            projectId: ($projectId === null || $projectId === '') ? null : (int) $projectId,
            tagIds: array_values(array_map('intval', $validated['tag_ids'] ?? [])),
        );
    }
}
