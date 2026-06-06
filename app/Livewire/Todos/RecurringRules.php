<?php

namespace App\Livewire\Todos;

use App\Actions\Todos\DeleteTodoRecurrenceRule;
use App\Actions\Todos\GenerateRecurringOccurrences;
use App\Actions\Todos\MoveRecurringOccurrence;
use App\Actions\Todos\RecordRecurringOccurrenceEdit;
use App\Actions\Todos\SaveTodoRecurrenceRule;
use App\Actions\Todos\SkipRecurringOccurrence;
use App\Actions\Todos\ToggleTodoRecurrenceRule;
use App\Actions\Todos\UpdateRecurringOccurrenceDetails;
use App\Actions\Todos\UpdateRecurringSeriesDetails;
use App\Data\Todos\RecurrenceGenerationResult;
use App\Data\Todos\RecurrenceRuleData;
use App\Data\Todos\RecurringOccurrenceDetailsData;
use App\Enums\Priority;
use App\Enums\RecurrenceEditScope;
use App\Enums\RecurrenceEndType;
use App\Enums\RecurrenceFrequency;
use App\Enums\RecurrenceWeekday;
use App\Exceptions\InvalidTodoTransition;
use App\Models\Todo;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use App\Queries\Todos\TodoRecurrenceRuleQuery;
use App\Rules\Todos\DueDate;
use App\Rules\Todos\OwnedActiveTodo;
use App\Rules\Todos\RecurrenceRule;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('todos.pages.recurring.title')]
class RecurringRules extends Component
{
    use AuthorizesRequests;

    public string $todoId = '';

    public string $frequency = 'weekly';

    public string $interval = '1';

    public string $startsOn = '';

    /** @var list<string> */
    public array $weekdays = [];

    public string $monthDay = '';

    public string $endType = 'never';

    public string $endsOn = '';

    public string $maxOccurrences = '';

    public bool $isEnabled = true;

    /**
     * Synthetic validated payload for the reusable custom rule.
     *
     * @var array<string, mixed>
     */
    public array $recurrenceRule = [];

    #[Locked]
    public ?int $editingRuleId = null;

    public string $editingTaskTitle = '';

    /**
     * @var array{matched: int, processed: int, created: int, skipped: int, failed: int, remaining: int, window: string}|null
     */
    #[Locked]
    public ?array $lastGenerationReport = null;

    #[Locked]
    public ?int $movingOccurrenceId = null;

    public string $moveTo = '';

    public string $exceptionNote = '';

    #[Locked]
    public ?int $editingOccurrenceId = null;

    public string $editScope = 'occurrence';

    public string $occurrenceEditTitle = '';

    public string $occurrenceEditPriority = 'normal';

    public string $occurrenceEditDueDate = '';

    public string $seriesEditTitle = '';

    public string $seriesEditPriority = 'normal';

    public function mount(): void
    {
        $this->authorize('viewAny', TodoRecurrenceRule::class);
        $this->resetForm();
    }

    public function render(): View
    {
        return view('livewire.todos.recurring-rules');
    }

    public function save(SaveTodoRecurrenceRule $saveRule, TodoRecurrenceRuleQuery $query): void
    {
        $user = $this->currentUser();
        $this->recurrenceRule = $this->payload();

        $this->validate($this->validationRules($user), messages: $this->validationMessages(), attributes: $this->validationAttributes());

        $data = RecurrenceRuleData::fromPayload($this->payload());
        $todo = $query->findEligibleTaskFor($user, (int) $this->todoId);
        $wasEditing = $this->editingRuleId !== null;

        $rule = $saveRule->handle($user, $todo, $data);

        $this->resetForm();
        $this->refreshRecurringState();

        Flux::toast(variant: 'success', text: $wasEditing
            ? __('todos.recurrence.messages.updated')
            : __('todos.recurrence.messages.created', ['task' => $rule->todo?->title ?? __('todos.recurrence.missing_task')]));
    }

    public function startEditRule(int $ruleId, TodoRecurrenceRuleQuery $query): void
    {
        $rule = $query->findFor($this->currentUser(), $ruleId);
        $this->authorize('update', $rule);

        $this->editingRuleId = $rule->id;
        $this->todoId = (string) $rule->todo_id;
        $this->editingTaskTitle = $rule->todo?->title ?? __('todos.recurrence.missing_task');
        $this->frequency = $rule->frequency->value;
        $this->interval = (string) $rule->interval;
        $this->startsOn = $rule->starts_on->toDateString();
        $this->weekdays = array_values($rule->weekdays ?? []);
        $this->monthDay = $rule->month_day === null ? '' : (string) $rule->month_day;
        $this->endType = $rule->end_type->value;
        $this->endsOn = $rule->ends_on?->toDateString() ?? '';
        $this->maxOccurrences = $rule->max_occurrences === null ? '' : (string) $rule->max_occurrences;
        $this->isEnabled = $rule->is_enabled;
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function toggleRule(int $ruleId, TodoRecurrenceRuleQuery $query, ToggleTodoRecurrenceRule $toggleRule): void
    {
        $rule = $query->findFor($this->currentUser(), $ruleId);

        try {
            $updated = $toggleRule->handle($this->currentUser(), $rule);
        } catch (ValidationException $exception) {
            $this->copyValidationErrors($exception);

            return;
        }

        $this->refreshRecurringState();

        Flux::toast(variant: 'success', text: $updated->is_enabled
            ? __('todos.recurrence.messages.enabled')
            : __('todos.recurrence.messages.paused'));
    }

    public function deleteRule(int $ruleId, TodoRecurrenceRuleQuery $query, DeleteTodoRecurrenceRule $deleteRule): void
    {
        $rule = $query->findFor($this->currentUser(), $ruleId);

        $deleteRule->handle($this->currentUser(), $rule);

        if ($this->editingRuleId === $ruleId) {
            $this->resetForm();
        }

        $this->refreshRecurringState();

        Flux::toast(variant: 'success', text: __('todos.recurrence.messages.deleted'));
    }

    public function generateOccurrences(GenerateRecurringOccurrences $generateOccurrences): void
    {
        $result = $generateOccurrences->handle($this->currentUser());
        $this->lastGenerationReport = $this->generationReportFrom($result);
        $this->refreshRecurringState();

        Flux::toast(variant: 'success', text: trans_choice('todos.recurrence.generation.messages.generated', $result->createdCount, [
            'count' => $result->createdCount,
        ]));
    }

    public function skipOccurrence(int $occurrenceId, SkipRecurringOccurrence $skipRecurringOccurrence): void
    {
        try {
            $skipRecurringOccurrence->handle($this->currentUser(), $occurrenceId);
        } catch (ValidationException $exception) {
            $this->copyValidationErrors($exception);

            return;
        }

        $this->refreshRecurringState();

        Flux::toast(variant: 'success', text: __('todos.recurrence.exceptions.messages.skipped'));
    }

    public function startMoveOccurrence(int $occurrenceId): void
    {
        $occurrence = $this->findGeneratedOccurrence($occurrenceId);
        $currentDate = $occurrence->due_date?->toDateString() ?? $occurrence->recurrence_occurs_on?->toDateString() ?? today()->toDateString();

        $this->movingOccurrenceId = $occurrence->id;
        $this->moveTo = CarbonImmutable::parse($currentDate)->addDay()->toDateString();
        $this->exceptionNote = '';
        $this->resetValidation(['moveTo', 'exceptionNote', 'recurrenceOccurrence']);

        Flux::modal('move-recurring-occurrence')->show();
    }

    public function moveOccurrence(MoveRecurringOccurrence $moveRecurringOccurrence): void
    {
        $this->validate([
            'movingOccurrenceId' => ['required', 'integer'],
            'moveTo' => ['required', 'date_format:Y-m-d'],
            'exceptionNote' => ['nullable', 'string', 'max:255'],
        ], attributes: [
            'moveTo' => __('todos.recurrence.exceptions.fields.move_to'),
            'exceptionNote' => __('todos.recurrence.exceptions.fields.note'),
        ]);

        try {
            $exception = $moveRecurringOccurrence->handle($this->currentUser(), (int) $this->movingOccurrenceId, $this->moveTo, $this->exceptionNote);
        } catch (ValidationException $exception) {
            $this->copyValidationErrors($exception);

            return;
        }

        $this->movingOccurrenceId = null;
        $this->moveTo = '';
        $this->exceptionNote = '';
        $this->refreshRecurringState();

        Flux::modal('move-recurring-occurrence')->close();
        Flux::toast(variant: 'success', text: __('todos.recurrence.exceptions.messages.moved', [
            'date' => $exception->adjusted_occurs_on?->toDateString() ?? __('todos.recurrence.none'),
        ]));
    }

    public function recordOccurrenceEdit(int $occurrenceId, RecordRecurringOccurrenceEdit $recordRecurringOccurrenceEdit): void
    {
        try {
            $recordRecurringOccurrenceEdit->handle($this->currentUser(), $occurrenceId);
        } catch (ValidationException $exception) {
            $this->copyValidationErrors($exception);

            return;
        }

        $this->refreshRecurringState();

        Flux::toast(variant: 'success', text: __('todos.recurrence.exceptions.messages.edited'));
    }

    public function startEditOccurrence(int $occurrenceId): void
    {
        try {
            $occurrence = $this->findGeneratedOccurrence($occurrenceId)->loadMissing('recurrenceSource');
        } catch (ValidationException $exception) {
            $this->copyValidationErrors($exception);

            return;
        }

        $this->authorize('update', $occurrence);

        $source = $occurrence->recurrenceSource instanceof Todo && $occurrence->recurrenceSource->isOwnedBy($this->currentUser())
            ? $occurrence->recurrenceSource
            : $occurrence;

        $this->editingOccurrenceId = $occurrence->id;
        $this->editScope = RecurrenceEditScope::Occurrence->value;
        $this->occurrenceEditTitle = $occurrence->title;
        $this->occurrenceEditPriority = $occurrence->priority->value;
        $this->occurrenceEditDueDate = $occurrence->due_date?->toDateString() ?? $occurrence->recurrence_occurs_on?->toDateString() ?? today()->toDateString();
        $this->seriesEditTitle = $source->title;
        $this->seriesEditPriority = $source->priority->value;
        $this->resetValidation($this->recurringEditValidationFields());

        Flux::modal('edit-recurring-occurrence')->show();
    }

    public function saveRecurringEdit(UpdateRecurringOccurrenceDetails $updateOccurrence, UpdateRecurringSeriesDetails $updateSeries): void
    {
        if ($this->editingOccurrenceId === null) {
            return;
        }

        $scope = $this->validatedEditScope();

        if (! $scope instanceof RecurrenceEditScope) {
            return;
        }

        try {
            if ($scope === RecurrenceEditScope::Occurrence) {
                $updateOccurrence->handle($this->currentUser(), $this->editingOccurrenceId, $this->occurrenceEditData());
                $message = __('todos.recurrence.edit_scope.messages.occurrence_updated');
            } else {
                $updated = $updateSeries->handle($this->currentUser(), $this->editingOccurrenceId, $this->seriesEditData());
                $message = trans_choice('todos.recurrence.edit_scope.messages.series_updated', $updated, ['count' => $updated]);
            }
        } catch (InvalidTodoTransition) {
            $this->addError('recurrenceOccurrence', __('todos.recurrence.edit_scope.validation.locked'));

            return;
        } catch (ValidationException $exception) {
            $this->copyValidationErrors($exception);

            return;
        }

        $this->resetRecurringEdit();
        $this->refreshRecurringState();

        Flux::modal('edit-recurring-occurrence')->close();
        Flux::toast(variant: 'success', text: $message);
    }

    public function updatedEditScope(): void
    {
        $this->resetValidation($this->recurringEditValidationFields());
    }

    public function updatedFrequency(): void
    {
        if ($this->frequency === RecurrenceFrequency::Weekly->value && $this->weekdays === []) {
            $this->weekdays = [RecurrenceWeekday::fromIsoWeekday((int) today()->dayOfWeekIso)->value];
        }

        if ($this->frequency !== RecurrenceFrequency::Weekly->value) {
            $this->weekdays = [];
        }

        if ($this->frequency === RecurrenceFrequency::Monthly->value && $this->monthDay === '') {
            $this->monthDay = (string) min(28, (int) today()->day);
        }

        if ($this->frequency !== RecurrenceFrequency::Monthly->value) {
            $this->monthDay = '';
        }
    }

    public function updatedEndType(): void
    {
        if ($this->endType !== RecurrenceEndType::OnDate->value) {
            $this->endsOn = '';
        }

        if ($this->endType !== RecurrenceEndType::AfterOccurrences->value) {
            $this->maxOccurrences = '';
        }

        if ($this->endType === RecurrenceEndType::OnDate->value && $this->endsOn === '') {
            $this->endsOn = today()->addMonths(3)->toDateString();
        }

        if ($this->endType === RecurrenceEndType::AfterOccurrences->value && $this->maxOccurrences === '') {
            $this->maxOccurrences = '12';
        }
    }

    /**
     * @return Collection<int, TodoRecurrenceRule>
     */
    #[Computed]
    public function recurrenceRules(): Collection
    {
        return app(TodoRecurrenceRuleQuery::class)->for($this->currentUser());
    }

    /**
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function taskOptions(): Collection
    {
        return app(TodoRecurrenceRuleQuery::class)->taskOptionsFor($this->currentUser());
    }

    /**
     * @return list<RecurrenceFrequency>
     */
    public function frequencyOptions(): array
    {
        return RecurrenceFrequency::cases();
    }

    /**
     * @return list<RecurrenceWeekday>
     */
    public function weekdayOptions(): array
    {
        return RecurrenceWeekday::cases();
    }

    /**
     * @return list<RecurrenceEndType>
     */
    public function endTypeOptions(): array
    {
        return RecurrenceEndType::cases();
    }

    /**
     * @return list<Priority>
     */
    public function priorityOptions(): array
    {
        return Priority::cases();
    }

    /**
     * @return list<RecurrenceEditScope>
     */
    public function editScopeOptions(): array
    {
        return RecurrenceEditScope::cases();
    }

    public function showWeekdays(): bool
    {
        return $this->frequency === RecurrenceFrequency::Weekly->value;
    }

    public function showMonthDay(): bool
    {
        return $this->frequency === RecurrenceFrequency::Monthly->value;
    }

    public function showEndDate(): bool
    {
        return $this->endType === RecurrenceEndType::OnDate->value;
    }

    public function showMaxOccurrences(): bool
    {
        return $this->endType === RecurrenceEndType::AfterOccurrences->value;
    }

    public function toggleActionLabel(TodoRecurrenceRule $rule): string
    {
        return $rule->is_enabled
            ? __('todos.recurrence.actions.pause')
            : __('todos.recurrence.actions.enable');
    }

    private function resetForm(): void
    {
        $this->todoId = '';
        $this->frequency = RecurrenceFrequency::Weekly->value;
        $this->interval = '1';
        $this->startsOn = today()->toDateString();
        $this->weekdays = [RecurrenceWeekday::fromIsoWeekday((int) today()->dayOfWeekIso)->value];
        $this->monthDay = '';
        $this->endType = RecurrenceEndType::Never->value;
        $this->endsOn = '';
        $this->maxOccurrences = '';
        $this->isEnabled = true;
        $this->recurrenceRule = [];
        $this->editingRuleId = null;
        $this->editingTaskTitle = '';
        $this->movingOccurrenceId = null;
        $this->moveTo = '';
        $this->exceptionNote = '';
        $this->resetRecurringEdit();
        $this->resetValidation();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'frequency' => $this->frequency,
            'interval' => $this->interval,
            'starts_on' => $this->startsOn,
            'weekdays' => $this->weekdays,
            'month_day' => $this->monthDay,
            'end_type' => $this->endType,
            'ends_on' => $this->endsOn,
            'max_occurrences' => $this->maxOccurrences,
            'is_enabled' => $this->isEnabled,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validationRules(User $user): array
    {
        return [
            'todoId' => ['required', 'integer', new OwnedActiveTodo($user)],
            'frequency' => ['required', Rule::enum(RecurrenceFrequency::class)],
            'interval' => ['required', 'integer', 'min:1', 'max:30'],
            'startsOn' => ['required', 'date_format:Y-m-d'],
            'weekdays' => ['array'],
            'weekdays.*' => ['string', Rule::in(RecurrenceWeekday::values())],
            'monthDay' => ['nullable', 'integer', 'min:1', 'max:31'],
            'endType' => ['required', Rule::enum(RecurrenceEndType::class)],
            'endsOn' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:startsOn'],
            'maxOccurrences' => ['nullable', 'integer', 'min:1', 'max:365'],
            'isEnabled' => ['boolean'],
            'recurrenceRule' => ['array', new RecurrenceRule],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'todoId.required' => __('todos.validation.owned_active_todo'),
            'todoId.integer' => __('todos.validation.owned_active_todo'),
            'weekdays.*.in' => __('todos.validation.recurrence_weekdays'),
            'endsOn.after_or_equal' => __('todos.validation.recurrence_ends_after_start'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationAttributes(): array
    {
        return [
            'todoId' => __('todos.recurrence.fields.task'),
            'frequency' => __('todos.recurrence.fields.frequency'),
            'interval' => __('todos.recurrence.fields.interval'),
            'startsOn' => __('todos.recurrence.fields.starts_on'),
            'weekdays' => __('todos.recurrence.fields.weekdays'),
            'weekdays.*' => __('todos.recurrence.fields.weekday'),
            'monthDay' => __('todos.recurrence.fields.month_day'),
            'endType' => __('todos.recurrence.fields.end_type'),
            'endsOn' => __('todos.recurrence.fields.ends_on'),
            'maxOccurrences' => __('todos.recurrence.fields.max_occurrences'),
            'isEnabled' => __('todos.recurrence.fields.is_enabled'),
            'recurrenceRule' => __('todos.recurrence.fields.rule'),
        ];
    }

    private function refreshRecurringState(): void
    {
        unset($this->recurrenceRules, $this->taskOptions);
    }

    private function findGeneratedOccurrence(int $occurrenceId): Todo
    {
        return app(TodoRecurrenceRuleQuery::class)->findGeneratedOccurrenceFor($this->currentUser(), $occurrenceId);
    }

    private function occurrenceEditData(): RecurringOccurrenceDetailsData
    {
        $this->validate([
            'occurrenceEditTitle' => ['required', 'string', 'max:120'],
            'occurrenceEditPriority' => ['required', Rule::enum(Priority::class)],
            'occurrenceEditDueDate' => ['required', 'date_format:Y-m-d', new DueDate],
        ], attributes: [
            'occurrenceEditTitle' => __('todos.recurrence.edit_scope.fields.occurrence_title'),
            'occurrenceEditPriority' => __('todos.recurrence.edit_scope.fields.occurrence_priority'),
            'occurrenceEditDueDate' => __('todos.recurrence.edit_scope.fields.occurrence_due_date'),
        ]);

        return RecurringOccurrenceDetailsData::occurrence(
            $this->occurrenceEditTitle,
            $this->occurrenceEditPriority,
            $this->occurrenceEditDueDate,
        );
    }

    private function seriesEditData(): RecurringOccurrenceDetailsData
    {
        $this->validate([
            'seriesEditTitle' => ['required', 'string', 'max:120'],
            'seriesEditPriority' => ['required', Rule::enum(Priority::class)],
        ], attributes: [
            'seriesEditTitle' => __('todos.recurrence.edit_scope.fields.series_title'),
            'seriesEditPriority' => __('todos.recurrence.edit_scope.fields.series_priority'),
        ]);

        return RecurringOccurrenceDetailsData::series(
            $this->seriesEditTitle,
            $this->seriesEditPriority,
        );
    }

    private function validatedEditScope(): ?RecurrenceEditScope
    {
        $this->validate([
            'editingOccurrenceId' => ['required', 'integer'],
            'editScope' => ['required', Rule::enum(RecurrenceEditScope::class)],
        ], attributes: [
            'editScope' => __('todos.recurrence.edit_scope.fields.scope'),
        ]);

        return RecurrenceEditScope::tryFrom($this->editScope);
    }

    private function resetRecurringEdit(): void
    {
        $this->editingOccurrenceId = null;
        $this->editScope = RecurrenceEditScope::Occurrence->value;
        $this->occurrenceEditTitle = '';
        $this->occurrenceEditPriority = Priority::Normal->value;
        $this->occurrenceEditDueDate = '';
        $this->seriesEditTitle = '';
        $this->seriesEditPriority = Priority::Normal->value;
    }

    /**
     * @return list<string>
     */
    private function recurringEditValidationFields(): array
    {
        return [
            'editingOccurrenceId',
            'editScope',
            'occurrenceEditTitle',
            'occurrenceEditPriority',
            'occurrenceEditDueDate',
            'seriesEditTitle',
            'seriesEditPriority',
            'recurrenceOccurrence',
        ];
    }

    /**
     * @return array{matched: int, processed: int, created: int, skipped: int, failed: int, remaining: int, window: string}
     */
    private function generationReportFrom(RecurrenceGenerationResult $result): array
    {
        return [
            'matched' => $result->matchedCount,
            'processed' => $result->processedRuleCount,
            'created' => $result->createdCount,
            'skipped' => $result->skippedRuleCount,
            'failed' => $result->failedCount,
            'remaining' => $result->remainingCount,
            'window' => $result->windowEnd->format('Y-m-d'),
        ];
    }

    private function copyValidationErrors(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $this->addError($field, $message);
            }
        }
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
