<?php

namespace App\Livewire\Todos;

use App\Actions\Todos\ArchiveTodo;
use App\Actions\Todos\ClearCompletedTodos;
use App\Actions\Todos\CreateTodo;
use App\Actions\Todos\DeleteTodo;
use App\Actions\Todos\ToggleTodoCompletion;
use App\Actions\Todos\UnarchiveTodo;
use App\Actions\Todos\UpdateTodo;
use App\Enums\TodoStatus;
use App\Exceptions\InvalidTodoTransition;
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
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The private task workspace.
 *
 * This component owns UI state only: it authorizes every action, resolves every
 * target through the owner-scoped query (never a raw client ID), and delegates
 * all mutations to action classes. The lifecycle it drives is:
 *
 *   active  ⇄ completed        (toggle completion)
 *   active/completed → archived (archive)   archived → active/completed (restore)
 *   any non-deleted → trashed   (delete; soft, recoverable by design)
 *
 * Archived tasks cannot be completed or edited until restored.
 */
#[Title('Todos')]
class Index extends Component
{
    use AuthorizesRequests;

    public TodoForm $form;

    public TodoForm $editForm;

    /** The visible lifecycle bucket; persisted in the URL and validated. */
    #[Url(as: 'tab')]
    public string $tab = 'active';

    /** The task currently open in the edit modal, if any. */
    public ?int $editingId = null;

    public bool $showEditModal = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Todo::class);

        if (! in_array($this->tab, TodoStatus::tabValues(), true)) {
            $this->tab = TodoStatus::Active->value;
        }
    }

    public function render(): View
    {
        return view('livewire.todos.index');
    }

    public function createTodo(CreateTodo $createTodo): void
    {
        $this->authorize('create', Todo::class);

        $createTodo->handle($this->currentUser(), $this->form->data());

        $this->form->reset();
        unset($this->todos, $this->summary);

        Flux::toast(variant: 'success', text: __('todos.messages.created'));
    }

    public function toggleTodo(int $todoId, TodoListQuery $query, ToggleTodoCompletion $toggle): void
    {
        $todo = $query->findVisibleFor($this->currentUser(), $todoId);

        $this->authorize('complete', $todo);

        try {
            $toggle->handle($todo);
        } catch (InvalidTodoTransition) {
            Flux::toast(variant: 'warning', text: __('todos.messages.cannot_toggle_archived'));
        }

        unset($this->todos, $this->summary);
    }

    public function startEdit(int $todoId, TodoListQuery $query): void
    {
        $todo = $query->findVisibleFor($this->currentUser(), $todoId);

        $this->authorize('update', $todo);

        if ($todo->isArchived()) {
            Flux::toast(variant: 'warning', text: __('todos.messages.cannot_edit_archived'));

            return;
        }

        $this->editingId = $todo->id;
        $this->editForm->setTitle($todo->title);
        $this->showEditModal = true;
    }

    public function saveEdit(TodoListQuery $query, UpdateTodo $update): void
    {
        $todo = $query->findVisibleFor($this->currentUser(), (int) $this->editingId);

        $this->authorize('update', $todo);

        try {
            $update->handle($todo, $this->editForm->data());
        } catch (InvalidTodoTransition) {
            Flux::toast(variant: 'warning', text: __('todos.messages.cannot_edit_archived'));

            return;
        }

        $this->closeEdit();
        unset($this->todos, $this->summary);

        Flux::toast(variant: 'success', text: __('todos.messages.updated'));
    }

    public function closeEdit(): void
    {
        $this->editForm->reset();
        $this->editingId = null;
        $this->showEditModal = false;
    }

    public function archiveTodo(int $todoId, TodoListQuery $query, ArchiveTodo $archive): void
    {
        $todo = $query->findVisibleFor($this->currentUser(), $todoId);

        $this->authorize('archive', $todo);

        $archive->handle($todo);
        unset($this->todos, $this->summary);

        Flux::toast(variant: 'success', text: __('todos.messages.archived'));
    }

    public function restoreTodo(int $todoId, TodoListQuery $query, UnarchiveTodo $unarchive): void
    {
        $todo = $query->findVisibleFor($this->currentUser(), $todoId);

        $this->authorize('restore', $todo);

        $unarchive->handle($todo);
        unset($this->todos, $this->summary);

        Flux::toast(variant: 'success', text: __('todos.messages.restored'));
    }

    public function deleteTodo(int $todoId, TodoListQuery $query, DeleteTodo $delete): void
    {
        $todo = $query->findVisibleFor($this->currentUser(), $todoId);

        $this->authorize('delete', $todo);

        $delete->handle($todo);
        unset($this->todos, $this->summary);

        Flux::toast(variant: 'success', text: __('todos.messages.deleted'));
    }

    public function clearCompleted(ClearCompletedTodos $clearCompleted): void
    {
        $this->authorize('clearCompleted', Todo::class);

        $deleted = $clearCompleted->handle($this->currentUser());
        unset($this->todos, $this->summary);

        if ($deleted > 0) {
            Flux::toast(variant: 'success', text: __('todos.messages.completed_cleared'));
        }
    }

    /**
     * The tasks for the currently selected lifecycle bucket.
     *
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function todos(): Collection
    {
        return app(TodoListQuery::class)
            ->forStatus($this->currentUser(), $this->currentTab())
            ->get();
    }

    /**
     * @return array{active: int, completed: int, archived: int}
     */
    #[Computed]
    public function summary(): array
    {
        return app(TodoListQuery::class)->summaryFor($this->currentUser());
    }

    private function currentTab(): TodoStatus
    {
        return TodoStatus::tryFrom($this->tab) ?? TodoStatus::Active;
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
