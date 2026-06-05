<?php

namespace App\Livewire\Todos;

use App\Actions\Todos\ClearCompletedTodos;
use App\Actions\Todos\CreateTodo;
use App\Actions\Todos\DeleteTodo;
use App\Actions\Todos\ToggleTodoCompletion;
use App\Livewire\Forms\Todos\TodoForm;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoListQuery;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Todos')]
class Index extends Component
{
    use AuthorizesRequests;

    public TodoForm $form;

    public function mount(): void
    {
        $this->authorize('viewAny', Todo::class);
    }

    public function render(): View
    {
        return view('livewire.todos.index');
    }

    public function createTodo(CreateTodo $createTodo): void
    {
        $this->authorize('create', Todo::class);

        $createTodo->handle(
            $this->currentUser(),
            $this->form->data(),
        );

        $this->form->reset();

        Flux::toast(variant: 'success', text: __('todos.messages.created'));
    }

    public function toggleTodo(int $todoId, TodoListQuery $todoListQuery, ToggleTodoCompletion $toggleTodoCompletion): void
    {
        $todo = $todoListQuery->findVisibleFor($this->currentUser(), $todoId);

        $this->authorize('complete', $todo);

        $toggleTodoCompletion->handle($todo);
    }

    public function deleteTodo(int $todoId, TodoListQuery $todoListQuery, DeleteTodo $deleteTodo): void
    {
        $todo = $todoListQuery->findVisibleFor($this->currentUser(), $todoId);

        $this->authorize('delete', $todo);

        $deleteTodo->handle($todo);

        Flux::toast(variant: 'success', text: __('todos.messages.deleted'));
    }

    public function clearCompleted(ClearCompletedTodos $clearCompletedTodos): void
    {
        $this->authorize('clearCompleted', Todo::class);

        $deleted = $clearCompletedTodos->handle($this->currentUser());

        if ($deleted > 0) {
            Flux::toast(variant: 'success', text: __('todos.messages.completed_cleared'));
        }
    }

    /**
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function todos(): Collection
    {
        return app(TodoListQuery::class)
            ->visibleFor($this->currentUser())
            ->get();
    }

    /**
     * @return array{remaining: int, completed: int}
     */
    #[Computed]
    public function summary(): array
    {
        return app(TodoListQuery::class)
            ->summaryFor($this->currentUser());
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
