<?php

namespace App\Livewire\Todos;

use App\Actions\Todos\CompleteTodo;
use App\Actions\Todos\RescheduleFocusedTodo;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoFocusQuery;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
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

    public function completeTodo(int $todoId, TodoFocusQuery $query, CompleteTodo $complete): void
    {
        $todo = $query->findFor($this->currentUser(), $todoId);
        $this->authorize('complete', $todo);

        $complete->handle($todo);
        $this->afterFocusAction();

        Flux::toast(variant: 'success', text: __('todos.messages.completed'));
    }

    public function deferTodo(int $todoId, TodoFocusQuery $query, RescheduleFocusedTodo $reschedule): void
    {
        $todo = $query->findFor($this->currentUser(), $todoId);

        $reschedule->defer($this->currentUser(), $todo);
        $this->afterFocusAction();

        Flux::toast(variant: 'success', text: __('todos.messages.focus_deferred'));
    }

    public function snoozeTodo(int $todoId, TodoFocusQuery $query, RescheduleFocusedTodo $reschedule): void
    {
        $todo = $query->findFor($this->currentUser(), $todoId);

        $reschedule->snooze($this->currentUser(), $todo);
        $this->afterFocusAction();

        Flux::toast(variant: 'success', text: __('todos.messages.focus_snoozed'));
    }

    public function completeSelected(TodoFocusQuery $query, CompleteTodo $complete): void
    {
        $todo = $this->selectedTodo($query);

        $this->completeTodo($todo->id, $query, $complete);
    }

    public function deferSelected(TodoFocusQuery $query, RescheduleFocusedTodo $reschedule): void
    {
        $todo = $this->selectedTodo($query);

        $this->deferTodo($todo->id, $query, $reschedule);
    }

    public function snoozeSelected(TodoFocusQuery $query, RescheduleFocusedTodo $reschedule): void
    {
        $todo = $this->selectedTodo($query);

        $this->snoozeTodo($todo->id, $query, $reschedule);
    }

    /**
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function focusTodos(): Collection
    {
        return app(TodoFocusQuery::class)->for($this->currentUser());
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

    private function afterFocusAction(): void
    {
        $this->selectedTodoId = null;
        unset($this->focusTodos);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
