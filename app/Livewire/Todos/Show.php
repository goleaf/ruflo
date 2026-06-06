<?php

namespace App\Livewire\Todos;

use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoListQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('todos.pages.show.title')]
class Show extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $todoId;

    public function mount(int $todo, TodoListQuery $query): void
    {
        $resolvedTodo = $query->findVisibleFor($this->currentUser(), $todo);

        $this->authorize('view', $resolvedTodo);

        $this->todoId = $resolvedTodo->id;
    }

    public function render(): View
    {
        return view('livewire.todos.show');
    }

    #[Computed]
    public function todo(): Todo
    {
        $todo = app(TodoListQuery::class)->findVisibleFor($this->currentUser(), $this->todoId);

        $this->authorize('view', $todo);

        return $todo;
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
