<?php

namespace App\Data\Todos;

use App\Enums\Priority;
use App\Rules\Todos\DueDate;
use Illuminate\Validation\ValidationException;

final readonly class RecurringOccurrenceDetailsData
{
    public function __construct(
        public string $title,
        public Priority $priority,
        public ?string $dueDate = null,
    ) {}

    /**
     * @throws ValidationException
     */
    public static function occurrence(string $title, string $priority, ?string $dueDate): self
    {
        return new self(
            title: self::title($title, 'occurrenceEditTitle'),
            priority: self::priority($priority, 'occurrenceEditPriority'),
            dueDate: self::requiredDueDate($dueDate),
        );
    }

    /**
     * @throws ValidationException
     */
    public static function series(string $title, string $priority): self
    {
        return new self(
            title: self::title($title, 'seriesEditTitle'),
            priority: self::priority($priority, 'seriesEditPriority'),
        );
    }

    /**
     * @throws ValidationException
     */
    private static function title(string $title, string $field): string
    {
        $title = trim($title);

        if ($title === '' || mb_strlen($title) > 120) {
            throw ValidationException::withMessages([
                $field => __('todos.validation.title'),
            ]);
        }

        return $title;
    }

    /**
     * @throws ValidationException
     */
    private static function priority(string $priority, string $field): Priority
    {
        return Priority::tryFrom($priority)
            ?? throw ValidationException::withMessages([
                $field => __('todos.validation.priority'),
            ]);
    }

    /**
     * @throws ValidationException
     */
    private static function requiredDueDate(?string $dueDate): string
    {
        return DueDate::normalize($dueDate)
            ?? throw ValidationException::withMessages([
                'occurrenceEditDueDate' => __('todos.validation.due_date'),
            ]);
    }
}
