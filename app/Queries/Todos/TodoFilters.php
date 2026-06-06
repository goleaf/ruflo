<?php

namespace App\Queries\Todos;

use App\Enums\Priority;
use App\Enums\TodoStatus;

/**
 * Validated, normalized filter/sort state for the task list.
 *
 * Every field is already constrained to a safe value before it reaches here
 * (see the Livewire component's validation), so the query object can apply it
 * without re-checking. Ownership is never expressed here — it is applied
 * unconditionally by {@see TodoListQuery}.
 */
final readonly class TodoFilters
{
    public function __construct(
        public TodoStatus $status = TodoStatus::Active,
        public ?string $search = null,
        public ?int $projectId = null,
        public bool $withoutProject = false,
        public ?int $tagId = null,
        public ?Priority $priority = null,
        public ?string $due = null,
        public string $sort = 'created',
        public string $direction = 'desc',
        public bool $hasInvalidFilter = false,
    ) {
        //
    }

    /**
     * The sort keys the list accepts.
     *
     * @return list<string>
     */
    public static function sortOptions(): array
    {
        return ['created', 'updated', 'due', 'priority', 'project', 'title'];
    }

    /**
     * The due-date buckets the list accepts.
     *
     * @return list<string>
     */
    public static function dueOptions(): array
    {
        return ['today', 'overdue', 'upcoming', 'blocked', 'with', 'without'];
    }
}
