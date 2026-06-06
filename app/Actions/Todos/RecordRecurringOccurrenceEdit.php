<?php

namespace App\Actions\Todos;

use App\Enums\RecurrenceExceptionType;
use App\Models\Todo;
use App\Models\TodoRecurrenceException;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class RecordRecurringOccurrenceEdit
{
    public function handle(User $user, int $occurrenceId, ?string $note = null): TodoRecurrenceException
    {
        $occurrence = $this->findGeneratedOccurrence($user, $occurrenceId);

        Gate::forUser($user)->authorize('update', $occurrence);

        $exception = TodoRecurrenceException::query()
            ->ownedBy($user)
            ->where('todo_recurrence_rule_id', $occurrence->recurrence_rule_id)
            ->whereDate('original_occurs_on', $occurrence->recurrence_occurs_on?->toDateString())
            ->first() ?? new TodoRecurrenceException;

        $exception->forceFill([
            'user_id' => $user->id,
            'todo_recurrence_rule_id' => $occurrence->recurrence_rule_id,
            'todo_id' => $occurrence->id,
            'type' => RecurrenceExceptionType::Edited,
            'original_occurs_on' => $occurrence->recurrence_occurs_on?->toDateString(),
            'adjusted_occurs_on' => null,
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

    private function cleanNote(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : mb_substr($note, 0, 255);
    }
}
