<?php

namespace App\Livewire\Todos;

use App\Actions\Todos\AddTodoDependency;
use App\Actions\Todos\CreateTodoChecklistItem;
use App\Actions\Todos\DeleteTodoChecklistItem;
use App\Actions\Todos\MoveTodoChecklistItem;
use App\Actions\Todos\RemoveTodoDependency;
use App\Actions\Todos\ToggleTodoChecklistItem;
use App\Actions\Todos\UpdateTodoChecklistItem;
use App\Enums\TodoStatus;
use App\Exceptions\InvalidTodoTransition;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoDependency;
use App\Models\User;
use App\Queries\Todos\TodoChecklistItemListQuery;
use App\Queries\Todos\TodoDependencyQuery;
use App\Queries\Todos\TodoListQuery;
use App\Rules\Todos\AcyclicTodoDependency;
use App\Rules\Todos\ChecklistItemTitle;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
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

    public string $newChecklistItemTitle = '';

    #[Locked]
    public ?int $editingChecklistItemId = null;

    public string $editingChecklistItemTitle = '';

    public string $dependencyTodoId = '';

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

    public function addDependency(AddTodoDependency $addDependency): void
    {
        if (! $this->canManageDependencies()) {
            $this->toastDependenciesLocked();

            return;
        }

        $this->validate(
            [
                'dependencyTodoId' => [
                    'required',
                    'integer',
                    new AcyclicTodoDependency($this->currentUser(), $this->todo),
                ],
            ],
            messages: [
                'dependencyTodoId.required' => __('todos.validation.todo_dependency'),
                'dependencyTodoId.integer' => __('todos.validation.todo_dependency'),
            ],
            attributes: [
                'dependencyTodoId' => __('todos.dependencies.fields.blocker'),
            ],
        );

        $addDependency->handle($this->currentUser(), $this->todo, (int) $this->dependencyTodoId);

        $this->dependencyTodoId = '';
        $this->refreshDependencies();

        Flux::toast(variant: 'success', text: __('todos.dependencies.messages.added'));
    }

    public function removeDependency(int $dependencyId, TodoDependencyQuery $query, RemoveTodoDependency $removeDependency): void
    {
        if (! $this->canManageDependencies()) {
            $this->toastDependenciesLocked();

            return;
        }

        $dependency = $query->findFor($this->currentUser(), $this->todo, $dependencyId);
        $this->authorize('delete', $dependency);

        $removeDependency->handle($this->currentUser(), $dependency);

        $this->refreshDependencies();

        Flux::toast(variant: 'success', text: __('todos.dependencies.messages.removed'));
    }

    public function createChecklistItem(CreateTodoChecklistItem $createChecklistItem): void
    {
        $this->authorize('update', $this->todo);
        $this->validateChecklistTitle('newChecklistItemTitle');

        try {
            $createChecklistItem->handle($this->currentUser(), $this->todo, $this->newChecklistItemTitle);
        } catch (InvalidTodoTransition) {
            $this->toastChecklistLocked();

            return;
        }

        $this->newChecklistItemTitle = '';
        $this->refreshChecklist();

        Flux::toast(variant: 'success', text: __('todos.messages.checklist_item_created'));
    }

    public function startEditChecklistItem(int $itemId, TodoChecklistItemListQuery $query): void
    {
        if (! $this->canManageChecklist()) {
            $this->toastChecklistLocked();

            return;
        }

        $item = $this->findChecklistItem($query, $itemId);
        $this->authorize('update', $item);

        $this->editingChecklistItemId = $item->id;
        $this->editingChecklistItemTitle = $item->title;
        $this->resetValidation('editingChecklistItemTitle');
    }

    public function saveChecklistItem(TodoChecklistItemListQuery $query, UpdateTodoChecklistItem $updateChecklistItem): void
    {
        if ($this->editingChecklistItemId === null) {
            return;
        }

        $this->validateChecklistTitle('editingChecklistItemTitle');

        $item = $this->findChecklistItem($query, $this->editingChecklistItemId);
        $this->authorize('update', $item);

        try {
            $updateChecklistItem->handle($this->currentUser(), $item, $this->editingChecklistItemTitle);
        } catch (InvalidTodoTransition) {
            $this->toastChecklistLocked();

            return;
        }

        $this->cancelChecklistEdit();
        $this->refreshChecklist();

        Flux::toast(variant: 'success', text: __('todos.messages.checklist_item_updated'));
    }

    public function cancelChecklistEdit(): void
    {
        $this->editingChecklistItemId = null;
        $this->editingChecklistItemTitle = '';
        $this->resetValidation('editingChecklistItemTitle');
    }

    public function toggleChecklistItem(int $itemId, TodoChecklistItemListQuery $query, ToggleTodoChecklistItem $toggleChecklistItem): void
    {
        $item = $this->findChecklistItem($query, $itemId);
        $this->authorize('update', $item);
        $wasCompleted = $item->is_completed;

        try {
            $toggleChecklistItem->handle($this->currentUser(), $item, ! $wasCompleted);
        } catch (InvalidTodoTransition) {
            $this->toastChecklistLocked();

            return;
        }

        $this->refreshChecklist();

        Flux::toast(
            variant: 'success',
            text: $wasCompleted
                ? __('todos.messages.checklist_item_reopened')
                : __('todos.messages.checklist_item_completed'),
        );
    }

    public function moveChecklistItem(int $itemId, string $direction, TodoChecklistItemListQuery $query, MoveTodoChecklistItem $moveChecklistItem): void
    {
        $item = $this->findChecklistItem($query, $itemId);
        $this->authorize('update', $item);

        try {
            $moveChecklistItem->handle($this->currentUser(), $item, $direction);
        } catch (InvalidTodoTransition) {
            $this->toastChecklistLocked();

            return;
        }

        $this->refreshChecklist();

        Flux::toast(variant: 'success', text: __('todos.messages.checklist_item_moved'));
    }

    public function deleteChecklistItem(int $itemId, TodoChecklistItemListQuery $query, DeleteTodoChecklistItem $deleteChecklistItem): void
    {
        $item = $this->findChecklistItem($query, $itemId);
        $this->authorize('delete', $item);

        try {
            $deleteChecklistItem->handle($this->currentUser(), $item);
        } catch (InvalidTodoTransition) {
            $this->toastChecklistLocked();

            return;
        }

        if ($this->editingChecklistItemId === $itemId) {
            $this->cancelChecklistEdit();
        }

        $this->refreshChecklist();

        Flux::toast(variant: 'success', text: __('todos.messages.checklist_item_deleted'));
    }

    #[Computed]
    public function todo(): Todo
    {
        $todo = app(TodoListQuery::class)->findVisibleFor($this->currentUser(), $this->todoId);

        $this->authorize('view', $todo);

        return $todo;
    }

    /**
     * @return Collection<int, TodoChecklistItem>
     */
    #[Computed]
    public function checklistItems(): Collection
    {
        return app(TodoChecklistItemListQuery::class)->forTodo($this->currentUser(), $this->todo);
    }

    /**
     * @return Collection<int, TodoDependency>
     */
    #[Computed]
    public function dependencies(): Collection
    {
        return app(TodoDependencyQuery::class)->forTodo($this->currentUser(), $this->todo);
    }

    /**
     * @return Collection<int, TodoDependency>
     */
    #[Computed]
    public function openDependencies(): Collection
    {
        return app(TodoDependencyQuery::class)->openForTodo($this->currentUser(), $this->todo);
    }

    /**
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function dependencyOptions(): Collection
    {
        return app(TodoDependencyQuery::class)->candidatesFor($this->currentUser(), $this->todo);
    }

    /**
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function blockingTasks(): Collection
    {
        return app(TodoDependencyQuery::class)->blockedBy($this->currentUser(), $this->todo);
    }

    /**
     * @return array{total: int, completed: int, percent: int}
     */
    #[Computed]
    public function checklistProgress(): array
    {
        $items = $this->checklistItems;
        $total = $items->count();
        $completed = $items->where('is_completed', true)->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'percent' => $total === 0 ? 0 : (int) round(($completed / $total) * 100),
        ];
    }

    public function canManageChecklist(): bool
    {
        return in_array($this->todo->status(), [TodoStatus::Active, TodoStatus::Completed], true);
    }

    public function canManageDependencies(): bool
    {
        return in_array($this->todo->status(), [TodoStatus::Active, TodoStatus::Completed], true);
    }

    private function findChecklistItem(TodoChecklistItemListQuery $query, int $itemId): TodoChecklistItem
    {
        return $query->findFor($this->currentUser(), $this->todo, $itemId);
    }

    private function validateChecklistTitle(string $property): void
    {
        $this->validate(
            [
                $property => ['required', 'string', 'max:120', new ChecklistItemTitle],
            ],
            attributes: [
                $property => __('todos.checklist.fields.item_title'),
            ],
        );
    }

    private function refreshChecklist(): void
    {
        unset($this->checklistItems, $this->checklistProgress);
    }

    private function refreshDependencies(): void
    {
        unset($this->dependencies, $this->openDependencies, $this->dependencyOptions, $this->blockingTasks, $this->todo);
    }

    private function toastChecklistLocked(): void
    {
        Flux::toast(variant: 'warning', text: __('todos.messages.cannot_change_checklist_archived'));
    }

    private function toastDependenciesLocked(): void
    {
        Flux::toast(variant: 'warning', text: __('todos.dependencies.messages.locked'));
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
