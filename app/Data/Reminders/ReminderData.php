<?php

namespace App\Data\Reminders;

use App\Rules\Reminders\ReminderAt;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final readonly class ReminderData
{
    public function __construct(
        public int $todoId,
        public Carbon $remindAt,
    ) {}

    /**
     * @param  array{todo_id?: int|string|null, remind_at?: string|null}  $input
     */
    public static function fromArray(array $input): self
    {
        $todoId = $input['todo_id'] ?? null;

        if (! is_int($todoId) && (! is_string($todoId) || ! ctype_digit($todoId))) {
            throw ValidationException::withMessages([
                'todo_id' => __('reminders.validation.todo_required'),
            ]);
        }

        $remindAt = $input['remind_at'] ?? null;

        if (! is_string($remindAt)) {
            throw ValidationException::withMessages([
                'remind_at' => __('reminders.validation.remind_at'),
            ]);
        }

        return new self(
            todoId: (int) $todoId,
            remindAt: ReminderAt::parse($remindAt),
        );
    }
}
