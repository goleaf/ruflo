<?php

namespace App\Actions\Habits;

use App\Models\Habit;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class LinkTodoToHabit
{
    public function handle(User $user, Habit $habit, Todo $todo): Todo
    {
        Gate::forUser($user)->authorize('update', $habit);
        Gate::forUser($user)->authorize('update', $todo);

        $this->assertTodoCanBeLinked($todo);

        $todo->forceFill([
            'habit_id' => $habit->id,
        ])->save();

        return $todo->refresh();
    }

    private function assertTodoCanBeLinked(Todo $todo): void
    {
        if ($todo->deleted_at === null && ! $todo->isArchived()) {
            return;
        }

        throw ValidationException::withMessages([
            'todo_id' => __('habits.validation.linkable_todo'),
        ]);
    }
}
