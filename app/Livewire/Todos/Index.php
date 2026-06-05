<?php

namespace App\Livewire\Todos;

use App\Models\Todo;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Todos')]
class Index extends Component
{
    public string $title = '';

    public function render(): View
    {
        return view('livewire.todos.index');
    }

    public function createTodo(): void
    {
        $this->title = trim($this->title);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:120'],
        ]);

        $this->currentUser()->todos()->create([
            'title' => $validated['title'],
        ]);

        $this->reset('title');

        Flux::toast(variant: 'success', text: __('Todo added.'));
    }

    public function toggleTodo(int $todoId): void
    {
        $todo = $this->todosForCurrentUser()->findOrFail($todoId);

        $todo->update([
            'is_completed' => ! $todo->is_completed,
        ]);
    }

    public function deleteTodo(int $todoId): void
    {
        $this->todosForCurrentUser()->findOrFail($todoId)->delete();

        Flux::toast(variant: 'success', text: __('Todo deleted.'));
    }

    public function clearCompleted(): void
    {
        $deleted = $this->todosForCurrentUser()
            ->where('is_completed', true)
            ->delete();

        if ($deleted > 0) {
            Flux::toast(variant: 'success', text: __('Completed todos cleared.'));
        }
    }

    /**
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function todos(): Collection
    {
        return $this->todosForCurrentUser()
            ->latest()
            ->get();
    }

    #[Computed]
    public function remainingCount(): int
    {
        return $this->todosForCurrentUser()
            ->where('is_completed', false)
            ->count();
    }

    #[Computed]
    public function completedCount(): int
    {
        return $this->todosForCurrentUser()
            ->where('is_completed', true)
            ->count();
    }

    /**
     * @return Builder<Todo>
     */
    private function todosForCurrentUser(): Builder
    {
        return Todo::query()
            ->whereBelongsTo($this->currentUser());
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
