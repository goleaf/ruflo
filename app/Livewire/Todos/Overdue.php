<?php

namespace App\Livewire\Todos;

use App\Actions\Todos\CompleteTodo;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoListQuery;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('todos.pages.overdue.title')]
class Overdue extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', Todo::class);
    }

    public function render(): View
    {
        return view('livewire.todos.overdue');
    }

    public function completeTodo(int $todoId, TodoListQuery $query, CompleteTodo $complete): void
    {
        $todo = $query->findOverdueFor($this->currentUser(), $todoId);

        $this->authorize('complete', $todo);

        $complete->handle($todo);

        unset($this->todos);

        Flux::toast(variant: 'success', text: __('todos.messages.completed'));
    }

    /**
     * @return LengthAwarePaginator<int, Todo>
     */
    #[Computed]
    public function todos(): LengthAwarePaginator
    {
        return app(TodoListQuery::class)
            ->overdueFor($this->currentUser())
            ->paginate(15);
    }

    public function todayLabel(): string
    {
        return today()->isoFormat('MMM D, YYYY');
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
