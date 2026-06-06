<?php

namespace App\Data\Todos;

use App\Enums\Priority;
use App\Enums\TodoStatus;
use App\Queries\Todos\TodoFilters;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Normalized saved task-view input.
 *
 * Saved views persist only bounded URL state. Project and tag ids remain
 * strings so applying a view follows the same Livewire URL path as a normal
 * filtered list, including the existing owner-scoped invalid-id handling.
 */
final readonly class SavedTodoViewData
{
    /**
     * @param  array{tab: string, search: string, project: string, tag: string, priorityFilter: string, due: string, sort: string, direction: string}  $criteria
     */
    public function __construct(
        public string $name,
        public array $criteria,
    ) {
        //
    }

    /**
     * @param array{
     *     name: string,
     *     criteria?: array<string, mixed>
     * } $validated
     *
     * @throws ValidationException
     */
    public static function fromArray(array $validated): self
    {
        $name = Str::of($validated['name'])->squish()->limit(80, '')->value();

        if ($name === '') {
            throw ValidationException::withMessages([
                'savedViewName' => __('todos.validation.saved_view_name'),
            ]);
        }

        $criteria = $validated['criteria'] ?? [];

        return new self(
            name: $name,
            criteria: self::normalizeCriteria(is_array($criteria) ? $criteria : []),
        );
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array{tab: string, search: string, project: string, tag: string, priorityFilter: string, due: string, sort: string, direction: string}
     */
    public static function normalizeCriteria(array $criteria): array
    {
        $tab = self::stringValue($criteria['tab'] ?? TodoStatus::Active->value);
        $tab = in_array($tab, TodoStatus::tabValues(), true) ? $tab : TodoStatus::Active->value;

        $due = self::stringValue($criteria['due'] ?? '');
        $due = $tab === TodoStatus::Active->value && in_array($due, TodoFilters::dueOptions(), true) ? $due : '';

        $sort = self::stringValue($criteria['sort'] ?? 'created');
        $sort = in_array($sort, TodoFilters::sortOptions(), true) ? $sort : 'created';

        $priorityFilter = self::stringValue($criteria['priorityFilter'] ?? '');
        $priorityFilter = in_array($priorityFilter, Priority::values(), true) ? $priorityFilter : '';

        return [
            'tab' => $tab,
            'search' => Str::of(self::stringValue($criteria['search'] ?? ''))->squish()->limit(120, '')->value(),
            'project' => self::projectValue($criteria['project'] ?? ''),
            'tag' => self::numericValue($criteria['tag'] ?? ''),
            'priorityFilter' => $priorityFilter,
            'due' => $due,
            'sort' => $sort,
            'direction' => self::stringValue($criteria['direction'] ?? '') === 'asc' ? 'asc' : 'desc',
        ];
    }

    private static function projectValue(mixed $value): string
    {
        $value = self::stringValue($value);

        if ($value === 'none') {
            return $value;
        }

        return self::numericValue($value);
    }

    private static function numericValue(mixed $value): string
    {
        $value = self::stringValue($value);

        return ctype_digit($value) ? (string) (int) $value : '';
    }

    private static function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
