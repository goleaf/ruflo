<?php

namespace App\Data\Todos;

use App\Enums\Priority;
use App\Enums\TaskTemplateKind;
use App\Rules\Todos\TemplateChecklistItems;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validated, normalized input for reusable task templates.
 */
final readonly class TodoTemplateData
{
    /**
     * @param  list<string>  $checklistItems
     */
    public function __construct(
        public string $name,
        public TaskTemplateKind $kind,
        public string $title,
        public Priority $priority = Priority::Normal,
        public ?int $dueOffsetDays = null,
        public ?string $projectName = null,
        public array $checklistItems = [],
        public string $visibility = 'private',
        public ?string $description = null,
    ) {}

    /**
     * @param array{
     *     name: string,
     *     kind?: string|null,
     *     title: string,
     *     priority?: string|null,
     *     due_offset_days?: int|string|null,
     *     project_name?: string|null,
     *     checklist_items?: array<int, mixed>|null,
     *     visibility?: string|null,
     *     description?: string|null
     * } $validated
     */
    public static function fromArray(array $validated): self
    {
        $kind = self::kindFrom($validated['kind'] ?? null);
        $checklistItems = TemplateChecklistItems::normalize($validated['checklist_items'] ?? []);

        if (count($checklistItems) > 10) {
            throw ValidationException::withMessages([
                'checklist_items' => __('todos.validation.template_checklist_items'),
            ]);
        }

        if (in_array($kind, [TaskTemplateKind::Checklist, TaskTemplateKind::Routine], true) && $checklistItems === []) {
            throw ValidationException::withMessages([
                'checklist_items' => __('todos.validation.template_checklist_items_required'),
            ]);
        }

        $projectName = self::optionalText($validated['project_name'] ?? null, 'project_name', 120);

        if ($kind === TaskTemplateKind::Project && $projectName === null) {
            throw ValidationException::withMessages([
                'project_name' => __('todos.validation.template_project_name_required'),
            ]);
        }

        return new self(
            name: self::requiredText($validated['name'] ?? null, 'name', 80),
            kind: $kind,
            title: self::requiredText($validated['title'] ?? null, 'title', 120),
            priority: self::priorityFrom($validated['priority'] ?? null),
            dueOffsetDays: self::offsetFrom($validated['due_offset_days'] ?? null),
            projectName: $projectName,
            checklistItems: $checklistItems,
            visibility: self::visibilityFrom($validated['visibility'] ?? null),
            description: self::optionalText($validated['description'] ?? null, 'description', 500),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'kind' => $this->kind,
            'visibility' => $this->visibility,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'due_offset_days' => $this->dueOffsetDays,
            'project_name' => $this->projectName,
            'checklist_items' => $this->checklistItems,
        ];
    }

    private static function kindFrom(mixed $kind): TaskTemplateKind
    {
        if ($kind instanceof TaskTemplateKind) {
            return $kind;
        }

        if (! is_scalar($kind) || $kind === '') {
            throw ValidationException::withMessages([
                'kind' => __('todos.validation.template_kind'),
            ]);
        }

        return TaskTemplateKind::tryFrom((string) $kind)
            ?? throw ValidationException::withMessages([
                'kind' => __('todos.validation.template_kind'),
            ]);
    }

    private static function priorityFrom(mixed $priority): Priority
    {
        if ($priority instanceof Priority) {
            return $priority;
        }

        if ($priority === null || $priority === '') {
            return Priority::Normal;
        }

        if (! is_scalar($priority)) {
            throw ValidationException::withMessages([
                'priority' => __('todos.validation.priority'),
            ]);
        }

        return Priority::tryFrom((string) $priority)
            ?? throw ValidationException::withMessages([
                'priority' => __('todos.validation.priority'),
            ]);
    }

    private static function offsetFrom(mixed $offset): ?int
    {
        if ($offset === null || $offset === '') {
            return null;
        }

        if (! is_numeric($offset)) {
            throw ValidationException::withMessages([
                'due_offset_days' => __('todos.validation.template_due_offset'),
            ]);
        }

        $offset = (int) $offset;

        if ($offset < 0 || $offset > 365) {
            throw ValidationException::withMessages([
                'due_offset_days' => __('todos.validation.template_due_offset'),
            ]);
        }

        return $offset;
    }

    private static function visibilityFrom(mixed $visibility): string
    {
        if ($visibility === null || $visibility === '') {
            return 'private';
        }

        if (! in_array($visibility, ['private', 'shared'], true)) {
            throw ValidationException::withMessages([
                'visibility' => __('todos.validation.template_visibility'),
            ]);
        }

        return (string) $visibility;
    }

    private static function requiredText(mixed $value, string $field, int $max): string
    {
        if (! is_string($value)) {
            throw ValidationException::withMessages([
                $field => __('todos.validation.template_name'),
            ]);
        }

        $normalized = Str::of($value)->squish()->value();

        if ($normalized === '' || mb_strlen($normalized) > $max) {
            throw ValidationException::withMessages([
                $field => __('todos.validation.template_name'),
            ]);
        }

        return $normalized;
    }

    private static function optionalText(mixed $value, string $field, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw ValidationException::withMessages([
                $field => __('todos.validation.template_name'),
            ]);
        }

        $normalized = Str::of($value)->squish()->value();

        if ($normalized === '') {
            return null;
        }

        if (mb_strlen($normalized) > $max) {
            throw ValidationException::withMessages([
                $field => __('todos.validation.template_name'),
            ]);
        }

        return $normalized;
    }
}
