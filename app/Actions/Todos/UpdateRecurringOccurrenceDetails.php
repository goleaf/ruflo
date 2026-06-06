<?php

namespace App\Actions\Todos;

use App\Data\Todos\RecurringOccurrenceDetailsData;
use App\Enums\RecurrenceExceptionType;
use App\Enums\TodoTransition;
use App\Events\TodoUpdated;
use App\Models\Todo;
use App\Models\TodoRecurrenceException;
use App\Models\User;
use App\Queries\Todos\TodoRecurrenceRuleQuery;
use Illuminate\Support\Facades\Gate;

final class UpdateRecurringOccurrenceDetails
{
    public function __construct(
        private readonly TodoLifecycleStateMachine $stateMachine,
        private readonly TodoRecurrenceRuleQuery $rules,
        private readonly MoveRecurringOccurrence $moveRecurringOccurrence,
        private readonly RecordRecurringOccurrenceEdit $recordRecurringOccurrenceEdit,
    ) {}

    public function handle(User $user, int $occurrenceId, RecurringOccurrenceDetailsData $data): ?TodoRecurrenceException
    {
        $occurrence = $this->rules->findGeneratedOccurrenceFor($user, $occurrenceId);

        Gate::forUser($user)->authorize('update', $occurrence);
        $this->stateMachine->assertCan($occurrence, TodoTransition::Update);

        $currentDueDate = $occurrence->due_date?->toDateString();
        $targetDueDate = $data->dueDate;
        $dateChanged = $targetDueDate !== null && $targetDueDate !== $currentDueDate;

        $exception = $dateChanged
            ? $this->moveRecurringOccurrence->handle($user, $occurrence->id, $targetDueDate, __('todos.recurrence.edit_scope.default_exception_note'))
            : $this->existingExceptionFor($user, $occurrence);

        $occurrence->refresh();

        $changedDetails = $occurrence->title !== $data->title || $occurrence->priority !== $data->priority;

        if ($changedDetails) {
            $occurrence->forceFill([
                'title' => $data->title,
                'priority' => $data->priority,
            ])->save();

            TodoUpdated::dispatch($occurrence);
        }

        if (! $dateChanged && (! $exception instanceof TodoRecurrenceException || $exception->type === RecurrenceExceptionType::Edited)) {
            $exception = $this->recordRecurringOccurrenceEdit->handle($user, $occurrence->id, __('todos.recurrence.edit_scope.default_exception_note'));
        }

        return $exception;
    }

    private function existingExceptionFor(User $user, Todo $occurrence): ?TodoRecurrenceException
    {
        return TodoRecurrenceException::query()
            ->ownedBy($user)
            ->where('todo_recurrence_rule_id', $occurrence->recurrence_rule_id)
            ->whereDate('original_occurs_on', $occurrence->recurrence_occurs_on?->toDateString())
            ->first();
    }
}
