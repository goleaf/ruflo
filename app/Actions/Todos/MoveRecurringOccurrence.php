<?php

namespace App\Actions\Todos;

use App\Enums\RecurrenceExceptionType;
use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\TodoRecurrenceException;
use App\Models\User;
use App\Rules\Todos\DueDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class MoveRecurringOccurrence
{
    public function handle(User $user, int $occurrenceId, string $newDate, ?string $note = null): TodoRecurrenceException
    {
        $occurrence = $this->findGeneratedOccurrence($user, $occurrenceId);
        $originalOccursOn = $occurrence->recurrence_occurs_on?->toDateString();
        $targetDate = DueDate::normalize($newDate);

        if ($targetDate === null) {
            throw ValidationException::withMessages([
                'moveTo' => __('todos.recurrence.exceptions.validation.adjusted_occurs_on'),
            ]);
        }

        if ($targetDate === $originalOccursOn) {
            throw ValidationException::withMessages([
                'moveTo' => __('todos.recurrence.exceptions.validation.move_date_changed'),
            ]);
        }

        Gate::forUser($user)->authorize('update', $occurrence);

        if ($this->targetDateExists($user, $occurrence, $targetDate)) {
            throw ValidationException::withMessages([
                'moveTo' => __('todos.recurrence.exceptions.validation.adjusted_occurs_on_unique'),
            ]);
        }

        $this->shiftPendingReminders($occurrence, $targetDate);

        $occurrence->forceFill([
            'due_date' => $targetDate,
        ])->save();

        $exception = TodoRecurrenceException::query()
            ->ownedBy($user)
            ->where('todo_recurrence_rule_id', $occurrence->recurrence_rule_id)
            ->whereDate('original_occurs_on', $originalOccursOn)
            ->first() ?? new TodoRecurrenceException;

        $exception->forceFill([
            'user_id' => $user->id,
            'todo_recurrence_rule_id' => $occurrence->recurrence_rule_id,
            'todo_id' => $occurrence->id,
            'type' => RecurrenceExceptionType::Moved,
            'original_occurs_on' => $originalOccursOn,
            'adjusted_occurs_on' => $targetDate,
            'note' => $this->cleanNote($note),
        ])->save();

        return $exception;
    }

    private function findGeneratedOccurrence(User $user, int $occurrenceId): Todo
    {
        $occurrence = Todo::query()
            ->ownedBy($user)
            ->whereKey($occurrenceId)
            ->whereNotNull('recurrence_rule_id')
            ->whereNotNull('recurrence_source_todo_id')
            ->whereNotNull('recurrence_occurs_on')
            ->first();

        if (! $occurrence instanceof Todo) {
            throw ValidationException::withMessages([
                'recurrenceOccurrence' => __('todos.recurrence.exceptions.validation.generated_occurrence'),
            ]);
        }

        return $occurrence;
    }

    private function targetDateExists(User $user, Todo $occurrence, string $targetDate): bool
    {
        return Todo::query()
            ->withTrashed()
            ->ownedBy($user)
            ->where('recurrence_rule_id', $occurrence->recurrence_rule_id)
            ->whereDate('due_date', $targetDate)
            ->where('id', '!=', $occurrence->id)
            ->exists();
    }

    private function shiftPendingReminders(Todo $occurrence, string $targetDate): void
    {
        $currentDueDate = CarbonImmutable::parse($occurrence->due_date?->toDateString() ?? $occurrence->recurrence_occurs_on?->toDateString())->startOfDay();
        $targetDueDate = CarbonImmutable::parse($targetDate)->startOfDay();
        $offsetSeconds = $targetDueDate->getTimestamp() - $currentDueDate->getTimestamp();

        Reminder::query()
            ->where('todo_id', $occurrence->id)
            ->where('status', ReminderStatus::Pending->value)
            ->whereNotNull('remind_at')
            ->get()
            ->each(function (Reminder $reminder) use ($offsetSeconds): void {
                $reminder->forceFill([
                    'remind_at' => $reminder->remind_at?->addSeconds($offsetSeconds),
                ])->save();
            });
    }

    private function cleanNote(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : mb_substr($note, 0, 255);
    }
}
