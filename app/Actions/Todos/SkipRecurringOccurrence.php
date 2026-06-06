<?php

namespace App\Actions\Todos;

use App\Enums\RecurrenceExceptionType;
use App\Models\Todo;
use App\Models\TodoRecurrenceException;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SkipRecurringOccurrence
{
    public function __construct(
        private readonly DeleteTodo $deleteTodo,
    ) {}

    public function handle(User $user, int $occurrenceId, ?string $note = null): TodoRecurrenceException
    {
        $occurrence = $this->findGeneratedOccurrence($user, $occurrenceId, includeTrashed: true);

        Gate::forUser($user)->authorize('delete', $occurrence);

        $exception = $this->recordException($user, $occurrence, $note);

        if (! $occurrence->trashed()) {
            $this->deleteTodo->handle($occurrence);
        }

        return $exception;
    }

    private function findGeneratedOccurrence(User $user, int $occurrenceId, bool $includeTrashed = false): Todo
    {
        $query = Todo::query()
            ->ownedBy($user)
            ->whereKey($occurrenceId)
            ->whereNotNull('recurrence_rule_id')
            ->whereNotNull('recurrence_source_todo_id')
            ->whereNotNull('recurrence_occurs_on');

        if ($includeTrashed) {
            $query->withTrashed();
        }

        $occurrence = $query->first();

        if (! $occurrence instanceof Todo) {
            throw ValidationException::withMessages([
                'recurrenceOccurrence' => __('todos.recurrence.exceptions.validation.generated_occurrence'),
            ]);
        }

        return $occurrence;
    }

    private function recordException(User $user, Todo $occurrence, ?string $note): TodoRecurrenceException
    {
        $exception = TodoRecurrenceException::query()
            ->ownedBy($user)
            ->where('todo_recurrence_rule_id', $occurrence->recurrence_rule_id)
            ->whereDate('original_occurs_on', $occurrence->recurrence_occurs_on?->toDateString())
            ->first() ?? new TodoRecurrenceException;

        $exception->forceFill([
            'user_id' => $user->id,
            'todo_recurrence_rule_id' => $occurrence->recurrence_rule_id,
            'todo_id' => $occurrence->id,
            'type' => RecurrenceExceptionType::Skipped,
            'original_occurs_on' => $occurrence->recurrence_occurs_on?->toDateString(),
            'adjusted_occurs_on' => null,
            'note' => $this->cleanNote($note),
        ])->save();

        return $exception;
    }

    private function cleanNote(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : mb_substr($note, 0, 255);
    }
}
