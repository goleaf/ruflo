<?php

namespace App\Livewire\Todos;

use App\Actions\Todos\CreateManualTimeEntry;
use App\Actions\Todos\DeleteTimeEntry;
use App\Actions\Todos\DiscardTimeEntryTimer;
use App\Actions\Todos\StartTimeEntryTimer;
use App\Actions\Todos\StopTimeEntryTimer;
use App\Data\Todos\TimeEntryData;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TimeEntryQuery;
use App\Rules\Todos\OwnedActiveProject;
use App\Rules\Todos\OwnedTodo;
use App\Rules\Todos\TimeEntryDuration;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('todos.pages.time.title')]
class Time extends Component
{
    use AuthorizesRequests;

    public ?string $timerTodoId = null;

    public ?string $timerProjectId = null;

    public ?string $manualTodoId = null;

    public ?string $manualProjectId = null;

    public string $manualMinutes = '30';

    public string $manualEntryDate = '';

    public string $manualNotes = '';

    public function mount(): void
    {
        $this->authorize('viewAny', TimeEntry::class);
        $this->manualEntryDate = today()->toDateString();
    }

    public function render(): View
    {
        return view('livewire.todos.time');
    }

    public function startTimer(StartTimeEntryTimer $startTimer): void
    {
        $this->validateTimerInput();

        $entry = $startTimer->handle(
            $this->currentUser(),
            $this->optionalId($this->timerTodoId),
            $this->optionalId($this->timerProjectId),
        );

        $this->reset(['timerTodoId', 'timerProjectId']);
        $this->refreshTimeState();

        Flux::toast(variant: 'success', text: __('todos.time.messages.timer_started', ['context' => $this->entryContext($entry)]));
    }

    public function stopTimer(TimeEntryQuery $query, StopTimeEntryTimer $stopTimer): void
    {
        $entry = $stopTimer->handle($this->currentUser(), $this->activeEntryOrFail($query));
        $this->refreshTimeState();

        Flux::toast(variant: 'success', text: __('todos.time.messages.timer_stopped', ['duration' => $this->formatSeconds($entry->duration_seconds)]));
    }

    public function discardTimer(TimeEntryQuery $query, DiscardTimeEntryTimer $discardTimer): void
    {
        $discardTimer->handle($this->currentUser(), $this->activeEntryOrFail($query));
        $this->refreshTimeState();

        Flux::toast(variant: 'success', text: __('todos.time.messages.timer_discarded'));
    }

    public function createManualEntry(CreateManualTimeEntry $createManualTimeEntry): void
    {
        $entry = $createManualTimeEntry->handle($this->currentUser(), $this->validatedManualEntryData());

        $this->reset(['manualTodoId', 'manualProjectId', 'manualMinutes', 'manualNotes']);
        $this->manualMinutes = '30';
        $this->manualEntryDate = today()->toDateString();
        $this->refreshTimeState();

        Flux::toast(variant: 'success', text: __('todos.time.messages.manual_created', ['duration' => $this->formatSeconds($entry->duration_seconds)]));
    }

    public function deleteEntry(int $entryId, TimeEntryQuery $query, DeleteTimeEntry $deleteTimeEntry): void
    {
        $deleteTimeEntry->handle($this->currentUser(), $query->findFor($this->currentUser(), $entryId));
        $this->refreshTimeState();

        Flux::toast(variant: 'success', text: __('todos.time.messages.deleted'));
    }

    #[Computed]
    public function activeEntry(): ?TimeEntry
    {
        return app(TimeEntryQuery::class)->activeFor($this->currentUser());
    }

    /**
     * @return Collection<int, TimeEntry>
     */
    #[Computed]
    public function recentEntries(): Collection
    {
        return app(TimeEntryQuery::class)->recentFor($this->currentUser());
    }

    /**
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function taskOptions(): Collection
    {
        return app(TimeEntryQuery::class)->taskOptionsFor($this->currentUser());
    }

    /**
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projectOptions(): Collection
    {
        return app(TimeEntryQuery::class)->projectOptionsFor($this->currentUser());
    }

    /**
     * @return array{today_seconds: int, week_seconds: int, total_seconds: int, active_seconds: int}
     */
    #[Computed]
    public function summary(): array
    {
        return app(TimeEntryQuery::class)->summaryFor($this->currentUser());
    }

    public function activeElapsedSeconds(): int
    {
        return $this->activeEntry?->elapsedSeconds() ?? 0;
    }

    public function formatSeconds(int $seconds): string
    {
        if ($seconds > 0 && $seconds < 60) {
            return __('todos.time.duration.under_minute');
        }

        $minutes = intdiv($seconds, 60);
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return __('todos.time.duration.hours_minutes', [
                'hours' => $hours,
                'minutes' => $remainingMinutes,
            ]);
        }

        return __('todos.time.duration.minutes', ['minutes' => $minutes]);
    }

    public function entryContext(TimeEntry $entry): string
    {
        if ($entry->todo instanceof Todo) {
            return $entry->todo->title;
        }

        if ($entry->project instanceof Project) {
            return $entry->project->name;
        }

        return __('todos.time.context.none');
    }

    public function entryDateLabel(TimeEntry $entry): string
    {
        return $entry->entry_date->isoFormat('MMM D, YYYY');
    }

    private function validateTimerInput(): void
    {
        $user = $this->currentUser();

        Validator::make(
            [
                'timerTodoId' => $this->timerTodoId,
                'timerProjectId' => $this->timerProjectId,
            ],
            [
                'timerTodoId' => ['nullable', 'integer', new OwnedTodo($user)],
                'timerProjectId' => ['nullable', 'integer', new OwnedActiveProject($user)],
            ],
            [
                'timerTodoId.integer' => __('todos.validation.owned_todo'),
                'timerProjectId.integer' => __('todos.validation.owned_active_project'),
            ],
            [
                'timerTodoId' => __('todos.time.fields.task'),
                'timerProjectId' => __('todos.time.fields.project'),
            ],
        )->validate();
    }

    private function validatedManualEntryData(): TimeEntryData
    {
        $user = $this->currentUser();

        $validated = Validator::make(
            [
                'manualTodoId' => $this->manualTodoId,
                'manualProjectId' => $this->manualProjectId,
                'manualMinutes' => $this->manualMinutes,
                'manualEntryDate' => $this->manualEntryDate,
                'manualNotes' => $this->manualNotes,
            ],
            [
                'manualTodoId' => ['nullable', 'integer', new OwnedTodo($user)],
                'manualProjectId' => ['nullable', 'integer', new OwnedActiveProject($user)],
                'manualMinutes' => ['required', 'integer', new TimeEntryDuration],
                'manualEntryDate' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                'manualNotes' => ['nullable', 'string', 'max:500'],
            ],
            [
                'manualTodoId.integer' => __('todos.validation.owned_todo'),
                'manualProjectId.integer' => __('todos.validation.owned_active_project'),
                'manualMinutes.required' => __('todos.validation.time_entry_duration'),
                'manualMinutes.integer' => __('todos.validation.time_entry_duration'),
                'manualEntryDate.required' => __('todos.validation.time_entry_date'),
                'manualEntryDate.date_format' => __('todos.validation.time_entry_date'),
                'manualEntryDate.before_or_equal' => __('todos.validation.time_entry_date'),
                'manualNotes.string' => __('todos.validation.time_entry_notes'),
                'manualNotes.max' => __('todos.validation.time_entry_notes'),
            ],
            [
                'manualTodoId' => __('todos.time.fields.task'),
                'manualProjectId' => __('todos.time.fields.project'),
                'manualMinutes' => __('todos.time.fields.minutes'),
                'manualEntryDate' => __('todos.time.fields.entry_date'),
                'manualNotes' => __('todos.time.fields.notes'),
            ],
        )->validate();

        return TimeEntryData::fromArray([
            'todo_id' => $validated['manualTodoId'] ?? null,
            'project_id' => $validated['manualProjectId'] ?? null,
            'duration_minutes' => $validated['manualMinutes'],
            'entry_date' => $validated['manualEntryDate'],
            'notes' => $validated['manualNotes'] ?? null,
        ]);
    }

    private function activeEntryOrFail(TimeEntryQuery $query): TimeEntry
    {
        $entry = $query->activeFor($this->currentUser());

        if ($entry instanceof TimeEntry) {
            return $entry;
        }

        throw ValidationException::withMessages([
            'timer' => __('todos.validation.time_entry_timer_required'),
        ]);
    }

    private function optionalId(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function refreshTimeState(): void
    {
        unset($this->activeEntry, $this->recentEntries, $this->summary);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
