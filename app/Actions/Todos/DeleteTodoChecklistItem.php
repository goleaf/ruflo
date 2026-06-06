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

class DeleteTodoChecklistItem
{
    public function __construct(
        private readonly TodoLifecycleStateMachine $stateMachine,
    ) {}

    public function handle(User $user, TodoChecklistItem $item): void
    {
        DB::transaction(function () use ($user, $item): void {
            $todo = $item->todo;

            Gate::forUser($user)->authorize('delete', $item);
            Gate::forUser($user)->authorize('update', $todo);
            $this->stateMachine->assertCan($todo, TodoTransition::Update);

            $item->delete();
            $this->resequence($user, $todo);

            TodoChecklistChanged::dispatch($todo, $item, 'deleted');
        });
    }

    private function resequence(User $user, Todo $todo): void
    {
        $this->remainingItems($user, $todo)
            ->values()
            ->each(function (TodoChecklistItem $item, int $index): void {
                $position = $index + 1;

                if ($item->position !== $position) {
                    $item->forceFill(['position' => $position])->save();
                }
            });
    }

    /**
     * @return Collection<int, TodoChecklistItem>
     */
    private function remainingItems(User $user, Todo $todo): Collection
    {
        return TodoChecklistItem::query()
            ->ownedBy($user)
            ->whereBelongsTo($todo)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }
}
