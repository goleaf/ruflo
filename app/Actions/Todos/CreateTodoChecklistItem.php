<?php

namespace App\Actions\Todos;

use App\Enums\TodoTransition;
use App\Events\TodoChecklistChanged;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateTodoChecklistItem
{
    public function __construct(
        private readonly TodoLifecycleStateMachine $stateMachine,
    ) {}

    public function handle(User $user, Todo $todo, string $title): TodoChecklistItem
    {
        Gate::forUser($user)->authorize('update', $todo);
        $this->stateMachine->assertCan($todo, TodoTransition::Update);
        $normalizedTitle = $this->normalizedTitle($title);

        $item = $todo->checklistItems()->make([
            'title' => $normalizedTitle,
            'position' => $this->nextPosition($todo),
        ]);

        $item->user()->associate($user);
        $item->save();

        TodoChecklistChanged::dispatch($todo, $item, 'created');

        return $item;
    }

    private function nextPosition(Todo $todo): int
    {
        return ((int) $todo->checklistItems()->max('position')) + 1;
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
