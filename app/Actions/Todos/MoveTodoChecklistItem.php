<?php

namespace App\Actions\Todos;

use App\Enums\TodoTransition;
use App\Events\TodoChecklistChanged;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MoveTodoChecklistItem
{
    public function __construct(
        private readonly TodoLifecycleStateMachine $stateMachine,
    ) {}

    public function handle(User $user, TodoChecklistItem $item, string $direction): TodoChecklistItem
    {
        if (! in_array($direction, ['up', 'down'], true)) {
            throw ValidationException::withMessages([
                'direction' => __('todos.validation.checklist_item_direction'),
            ]);
        }

        return DB::transaction(function () use ($user, $item, $direction): TodoChecklistItem {
            $todo = $item->todo;

            Gate::forUser($user)->authorize('update', $item);
            Gate::forUser($user)->authorize('update', $todo);
            $this->stateMachine->assertCan($todo, TodoTransition::Update);

            $items = $this->orderedItems($user, $todo);
            $currentIndex = $items->search(fn (TodoChecklistItem $candidate): bool => $candidate->is($item));

            if ($currentIndex === false) {
                return $item;
            }

            $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
            $target = $items->get($targetIndex);

            if (! $target instanceof TodoChecklistItem) {
                return $item;
            }

            $currentPosition = $item->position;

            $item->forceFill(['position' => $target->position])->save();
            $target->forceFill(['position' => $currentPosition])->save();

            TodoChecklistChanged::dispatch($todo, $item, 'moved');

            return $item->refresh();
        });
    }

    /**
     * @return Collection<int, TodoChecklistItem>
     */
    private function orderedItems(User $user, Todo $todo): Collection
    {
        return TodoChecklistItem::query()
            ->ownedBy($user)
            ->whereBelongsTo($todo)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }
}
