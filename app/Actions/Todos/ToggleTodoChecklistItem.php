<?php

namespace App\Actions\Todos;

use App\Enums\TodoTransition;
use App\Events\TodoChecklistChanged;
use App\Models\TodoChecklistItem;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ToggleTodoChecklistItem
{
    public function __construct(
        private readonly TodoLifecycleStateMachine $stateMachine,
    ) {}

    public function handle(User $user, TodoChecklistItem $item, bool $completed): TodoChecklistItem
    {
        Gate::forUser($user)->authorize('update', $item);
        Gate::forUser($user)->authorize('update', $item->todo);
        $this->stateMachine->assertCan($item->todo, TodoTransition::Update);

        if ($item->is_completed === $completed) {
            return $item;
        }

        $item->forceFill([
            'is_completed' => $completed,
            'completed_at' => $completed ? now() : null,
        ])->save();

        TodoChecklistChanged::dispatch($item->todo, $item, $completed ? 'completed' : 'reopened');

        return $item->refresh();
    }
}
