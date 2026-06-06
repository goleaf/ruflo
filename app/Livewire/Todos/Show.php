<?php

namespace App\Livewire\Todos;

use App\Actions\Todos\CreateTodoChecklistItem;
use App\Actions\Todos\DeleteTodoChecklistItem;
use App\Actions\Todos\MoveTodoChecklistItem;
use App\Actions\Todos\ToggleTodoChecklistItem;
use App\Actions\Todos\UpdateTodoChecklistItem;
use App\Enums\TodoStatus;
use App\Exceptions\InvalidTodoTransition;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\User;
use App\Queries\Todos\TodoChecklistItemListQuery;
use App\Queries\Todos\TodoListQuery;
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

    private function toastChecklistLocked(): void
    {
        Flux::toast(variant: 'warning', text: __('todos.messages.cannot_change_checklist_archived'));
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
