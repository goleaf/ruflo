<?php

namespace App\Data\Todos;

use App\Queries\Todos\TimeEntryQuery;
use App\Rules\Todos\TimeEntryDuration;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class TimeEntryData
{
    /**
     * Validated, normalized input for manual time entries.
     */
    public function __construct(
        public ?int $todoId,
        public ?int $projectId,
        public int $durationMinutes,
        public string $entryDate,
        public ?string $notes = null,
    ) {}

    /**
     * @param array{
     *     todo_id?: int|string|null,
     *     project_id?: int|string|null,
     *     duration_minutes?: int|string|null,
     *     entry_date?: string|null,
     *     notes?: string|null,
     * } $validated
     */
    public static function fromArray(array $validated): self
    {
        $minutes = TimeEntryDuration::normalizeMinutes($validated['duration_minutes'] ?? null);

        if ($minutes === null || $minutes < TimeEntryDuration::MinMinutes || $minutes > TimeEntryDuration::MaxMinutes) {
            throw ValidationException::withMessages([
                'duration_minutes' => __('todos.validation.time_entry_duration'),
            ]);
        }

        $entryDate = $validated['entry_date'] ?? null;

        if (! is_string($entryDate)) {
            throw ValidationException::withMessages([
                'entry_date' => __('todos.validation.time_entry_date'),
            ]);
        }

        app(TimeEntryQuery::class)->parseEntryDate($entryDate);

        return new self(
            todoId: self::optionalId($validated['todo_id'] ?? null, 'todo_id'),
            projectId: self::optionalId($validated['project_id'] ?? null, 'project_id'),
            durationMinutes: $minutes,
            entryDate: $entryDate,
            notes: self::optionalNotes($validated['notes'] ?? null),
        );
    }

    private static function optionalId(mixed $value, string $field): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        throw ValidationException::withMessages([
            $field => __('todos.validation.time_entry_context'),
        ]);
    }

    private static function optionalNotes(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw ValidationException::withMessages([
                'notes' => __('todos.validation.time_entry_notes'),
            ]);
        }

        $normalized = Str::of($value)->squish()->value();

        if ($normalized === '') {
            return null;
        }

        if (mb_strlen($normalized) > 500) {
            throw ValidationException::withMessages([
                'notes' => __('todos.validation.time_entry_notes'),
            ]);
        }

        return $normalized;
    }
}
