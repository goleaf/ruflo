<?php

namespace App\Livewire\Todos;

use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoListQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('todos.pages.blocked.title')]
class Blocked extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', Todo::class);
    }

    public function render(): View
    {
        return view('livewire.todos.blocked');
    }

    /**
     * @return LengthAwarePaginator<int, Todo>
     */
    #[Computed]
    public function todos(): LengthAwarePaginator
    {
        return app(TodoListQuery::class)
            ->blockedFor($this->currentUser())
            ->paginate(15);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
