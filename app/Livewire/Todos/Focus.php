<?php

namespace App\Livewire\Todos;

use App\Actions\Todos\AbandonPomodoroSession;
use App\Actions\Todos\CompletePomodoroSession;
use App\Actions\Todos\CompleteTodo;
use App\Actions\Todos\PausePomodoroSession;
use App\Actions\Todos\RescheduleFocusedTodo;
use App\Actions\Todos\ResumePomodoroSession;
use App\Actions\Todos\StartPomodoroSession;
use App\Models\PomodoroSession;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\PomodoroSessionQuery;
use App\Queries\Todos\TodoFocusQuery;
use App\Rules\Todos\PomodoroDuration;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('todos.pages.focus.title')]
class Focus extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public ?int $selectedTodoId = null;

    public string $durationMinutes = '25';

    public function mount(): void
    {
        $this->authorize('viewAny', Todo::class);
    }

    public function render(): View
    {
        return view('livewire.todos.focus');
    }

    public function selectTask(int $todoId, TodoFocusQuery $query): void
    {
        $todo = $query->findFor($this->currentUser(), $todoId);
        $this->authorize('view', $todo);

        $this->selectedTodoId = $todo->id;
    }

    public function completeTodo(
        int $todoId,
        TodoFocusQuery $query,
        CompleteTodo $complete,
        PomodoroSessionQuery $sessions,
        CompletePomodoroSession $completeSession,
    ): void {
        $todo = $query->findFor($this->currentUser(), $todoId);
        $this->authorize('complete', $todo);

        $complete->handle($todo);
        $this->completeActiveSessionFor($todo, $sessions, $completeSession);
        $this->afterFocusAction();

        Flux::toast(variant: 'success', text: __('todos.messages.completed'));
    }

    public function deferTodo(
        int $todoId,
        TodoFocusQuery $query,
        RescheduleFocusedTodo $reschedule,
        PomodoroSessionQuery $sessions,
        AbandonPomodoroSession $abandonSession,
    ): void {
        $todo = $query->findFor($this->currentUser(), $todoId);

        $reschedule->defer($this->currentUser(), $todo);
        $this->abandonActiveSessionFor($todo, $sessions, $abandonSession);
        $this->afterFocusAction();

        Flux::toast(variant: 'success', text: __('todos.messages.focus_deferred'));
    }

    public function snoozeTodo(
        int $todoId,
        TodoFocusQuery $query,
        RescheduleFocusedTodo $reschedule,
        PomodoroSessionQuery $sessions,
        AbandonPomodoroSession $abandonSession,
    ): void {
        $todo = $query->findFor($this->currentUser(), $todoId);

        $reschedule->snooze($this->currentUser(), $todo);
        $this->abandonActiveSessionFor($todo, $sessions, $abandonSession);
        $this->afterFocusAction();

        Flux::toast(variant: 'success', text: __('todos.messages.focus_snoozed'));
    }

    public function completeSelected(
        TodoFocusQuery $query,
        CompleteTodo $complete,
        PomodoroSessionQuery $sessions,
        CompletePomodoroSession $completeSession,
    ): void {
        $todo = $this->selectedTodo($query);

        $this->completeTodo($todo->id, $query, $complete, $sessions, $completeSession);
    }

    public function deferSelected(
        TodoFocusQuery $query,
        RescheduleFocusedTodo $reschedule,
        PomodoroSessionQuery $sessions,
        AbandonPomodoroSession $abandonSession,
    ): void {
        $todo = $this->selectedTodo($query);

        $this->deferTodo($todo->id, $query, $reschedule, $sessions, $abandonSession);
    }

    public function snoozeSelected(
        TodoFocusQuery $query,
        RescheduleFocusedTodo $reschedule,
        PomodoroSessionQuery $sessions,
        AbandonPomodoroSession $abandonSession,
    ): void {
        $todo = $this->selectedTodo($query);

        $this->snoozeTodo($todo->id, $query, $reschedule, $sessions, $abandonSession);
    }

    public function startFocusSession(TodoFocusQuery $query, StartPomodoroSession $start): void
    {
        $todo = $this->selectedTodo($query);
        $session = $start->handle($this->currentUser(), $todo, (int) $this->durationMinutes);

        $this->selectedTodoId = $session->todo_id;
        $this->refreshPomodoroState();

        Flux::toast(variant: 'success', text: __('todos.messages.pomodoro_started', ['title' => $todo->title]));
    }

    public function pauseFocusSession(PomodoroSessionQuery $sessions, PausePomodoroSession $pause): void
    {
        $pause->handle($this->currentUser(), $this->activeSessionOrFail($sessions));
        $this->refreshPomodoroState();

        Flux::toast(variant: 'success', text: __('todos.messages.pomodoro_paused'));
    }

    public function resumeFocusSession(PomodoroSessionQuery $sessions, ResumePomodoroSession $resume): void
    {
        $resume->handle($this->currentUser(), $this->activeSessionOrFail($sessions));
        $this->refreshPomodoroState();

        Flux::toast(variant: 'success', text: __('todos.messages.pomodoro_resumed'));
    }

    public function completeFocusSession(PomodoroSessionQuery $sessions, CompletePomodoroSession $completeSession): void
    {
        $completeSession->handle($this->currentUser(), $this->activeSessionOrFail($sessions));
        $this->refreshPomodoroState();

        Flux::toast(variant: 'success', text: __('todos.messages.pomodoro_completed'));
    }

    public function abandonFocusSession(PomodoroSessionQuery $sessions, AbandonPomodoroSession $abandonSession): void
    {
        $abandonSession->handle($this->currentUser(), $this->activeSessionOrFail($sessions));
        $this->refreshPomodoroState();

        Flux::toast(variant: 'success', text: __('todos.messages.pomodoro_abandoned'));
    }

    /**
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function focusTodos(): Collection
    {
        return app(TodoFocusQuery::class)->for($this->currentUser());
    }

    #[Computed]
    public function activeSession(): ?PomodoroSession
    {
        return app(PomodoroSessionQuery::class)->activeFor($this->currentUser());
    }

    /**
     * @return list<int>
     */
    public function durationOptions(): array
    {
        return PomodoroDuration::Options;
    }

    public function activeSessionElapsedSeconds(): int
    {
        return $this->activeSession?->accruedSeconds() ?? 0;
    }

    public function activeSessionRemainingSeconds(): int
    {
        return $this->activeSession?->remainingSeconds() ?? ((int) $this->durationMinutes * 60);
    }

    public function activeSessionProgress(): int
    {
        if (! $this->activeSession instanceof PomodoroSession) {
            return 0;
        }

        return (int) min(100, floor(($this->activeSessionElapsedSeconds() / $this->activeSession->durationSeconds()) * 100));
    }

    public function sessionStatusColor(PomodoroSession $session): string
    {
        return match ($session->status->value) {
            'running' => 'green',
            'paused' => 'amber',
            'completed' => 'blue',
            'abandoned' => 'zinc',
        };
    }

    public function isSelected(Todo $todo): bool
    {
        if ($this->selectedTodoId === null) {
            return $this->focusTodos->first()?->is($todo) ?? false;
        }

        return $todo->id === $this->selectedTodoId;
    }

    public function dueBadgeColor(Todo $todo): string
    {
        return match (true) {
            $todo->isOverdue() => 'red',
            $todo->isDueToday() => 'amber',
            $todo->isUpcoming() => 'zinc',
            default => 'blue',
        };
    }

    public function dueBadgeLabel(Todo $todo): string
    {
        return match (true) {
            $todo->due_date === null => __('todos.fields.no_due_date'),
            $todo->isOverdue() => __('todos.filters.overdue'),
            $todo->isDueToday() => __('todos.filters.due_today'),
            default => $todo->due_date->isoFormat('MMM D'),
        };
    }

    private function selectedTodo(TodoFocusQuery $query): Todo
    {
        $todoId = $this->selectedTodoId ?? $this->focusTodos->first()?->id;

        abort_if($todoId === null, 404);

        return $query->findFor($this->currentUser(), $todoId);
    }

    private function activeSessionOrFail(PomodoroSessionQuery $sessions): PomodoroSession
    {
        $session = $sessions->activeFor($this->currentUser());

        if ($session instanceof PomodoroSession) {
            return $session;
        }

        throw ValidationException::withMessages([
            'session' => __('todos.validation.pomodoro_active_session_required'),
        ]);
    }

    private function completeActiveSessionFor(
        Todo $todo,
        PomodoroSessionQuery $sessions,
        CompletePomodoroSession $completeSession,
    ): void {
        $session = $sessions->activeForTodo($this->currentUser(), $todo->id);

        if ($session instanceof PomodoroSession) {
            $completeSession->handle($this->currentUser(), $session);
        }
    }

    private function abandonActiveSessionFor(
        Todo $todo,
        PomodoroSessionQuery $sessions,
        AbandonPomodoroSession $abandonSession,
    ): void {
        $session = $sessions->activeForTodo($this->currentUser(), $todo->id);

        if ($session instanceof PomodoroSession) {
            $abandonSession->handle($this->currentUser(), $session);
        }
    }

    private function afterFocusAction(): void
    {
        $this->selectedTodoId = null;
        $this->refreshPomodoroState();
        unset($this->focusTodos);
    }

    private function refreshPomodoroState(): void
    {
        unset($this->activeSession);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
