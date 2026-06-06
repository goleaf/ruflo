<?php

namespace App\Actions\Todos;

use App\Enums\TodoTransition;
use App\Events\TodoChecklistChanged;
use App\Models\TodoChecklistItem;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateTodoChecklistItem
{
    public function __construct(
        private readonly TodoLifecycleStateMachine $stateMachine,
    ) {}

    public function handle(User $user, TodoChecklistItem $item, string $title): TodoChecklistItem
    {
        Gate::forUser($user)->authorize('update', $item);
        Gate::forUser($user)->authorize('update', $item->todo);
        $this->stateMachine->assertCan($item->todo, TodoTransition::Update);
        $normalizedTitle = $this->normalizedTitle($title);

        $item->forceFill([
            'title' => $normalizedTitle,
        ])->save();

        TodoChecklistChanged::dispatch($item->todo, $item, 'updated');

        return $item->refresh();
    }

    private function normalizedTitle(string $title): string
    {
        $normalizedTitle = Str::of($title)->squish()->value();

        if ($normalizedTitle === '' || mb_strlen($normalizedTitle) > 120) {
            throw ValidationException::withMessages([
                'title' => __('todos.validation.checklist_item_title'),
            ]);
        }

        return $normalizedTitle;
    }
}
