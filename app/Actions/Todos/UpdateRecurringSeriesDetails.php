<?php

namespace App\Actions\Todos;

use App\Data\Todos\RecurringOccurrenceDetailsData;
use App\Enums\TodoTransition;
use App\Events\TodoUpdated;
use App\Models\Todo;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use App\Queries\Todos\TodoRecurrenceRuleQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class UpdateRecurringSeriesDetails
{
    public function __construct(
        private readonly TodoLifecycleStateMachine $stateMachine,
        private readonly TodoRecurrenceRuleQuery $rules,
    ) {}

    public function handle(User $user, int $occurrenceId, RecurringOccurrenceDetailsData $data): int
    {
        $occurrence = $this->rules->findGeneratedOccurrenceFor($user, $occurrenceId);

        Gate::forUser($user)->authorize('update', $occurrence);
        $this->stateMachine->assertCan($occurrence, TodoTransition::Update);

        $rule = TodoRecurrenceRule::query()
            ->ownedBy($user)
            ->whereKey($occurrence->recurrence_rule_id)
            ->with(['todo' => fn ($query) => $query->where('todos.user_id', $user->id)])
            ->first();

        if (! $rule instanceof TodoRecurrenceRule || ! $rule->todo instanceof Todo) {
            throw ValidationException::withMessages([
                'recurrenceOccurrence' => __('todos.recurrence.exceptions.validation.generated_occurrence'),
            ]);
        }

        Gate::forUser($user)->authorize('update', $rule);
        Gate::forUser($user)->authorize('update', $rule->todo);
        $this->stateMachine->assertCan($rule->todo, TodoTransition::Update);

        return DB::transaction(function () use ($user, $occurrence, $rule, $data): int {
            $updated = 0;

            $this->updateTaskDetails($rule->todo, $data->title, $data->priority->value);

            $occurrences = Todo::query()
                ->ownedBy($user)
                ->where('recurrence_rule_id', $rule->id)
                ->whereDate('recurrence_occurs_on', '>=', $occurrence->recurrence_occurs_on?->toDateString())
                ->whereNull('archived_at')
                ->where('is_completed', false)
                ->where(function (Builder $query) use ($occurrence): void {
                    $query
                        ->whereKey($occurrence->id)
                        ->orWhereDoesntHave('recurrenceException');
                })
                ->orderBy('recurrence_occurs_on')
                ->orderBy('id')
                ->get();

            foreach ($occurrences as $seriesOccurrence) {
                $title = __('todos.recurrence.generation.occurrence_title', [
                    'task' => $data->title,
                    'date' => $seriesOccurrence->recurrence_occurs_on?->isoFormat('MMM D, YYYY') ?? __('todos.recurrence.none'),
                ]);

                $this->updateTaskDetails($seriesOccurrence, $title, $data->priority->value);
                $updated++;
            }

            return $updated;
        });
    }

    private function updateTaskDetails(Todo $todo, string $title, string $priority): void
    {
        $todo->forceFill([
            'title' => $title,
            'priority' => $priority,
        ])->save();

        TodoUpdated::dispatch($todo);
    }
}
