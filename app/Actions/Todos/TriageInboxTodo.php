<?php

namespace App\Actions\Todos;

use App\Data\Todos\TodoData;
use App\Models\Todo;
use App\Models\User;
use App\Rules\Todos\InboxCaptureTitle;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Organizes one captured inbox task and removes it from the inbox.
 */
final class TriageInboxTodo
{
    public function __construct(
        private readonly UpdateTodo $updateTodo,
    ) {}

    public function handle(User $user, Todo $todo, TodoData $data): Todo
    {
        Gate::forUser($user)->authorize('update', $todo);
        $this->assertInboxTodo($todo);
        $this->validateTitle($data->title);

        $updatedTodo = $this->updateTodo->handle($user, $todo, $data);

        $updatedTodo->forceFill([
            'inbox_captured_at' => null,
        ])->save();

        return $updatedTodo->refresh();
    }

    private function assertInboxTodo(Todo $todo): void
    {
        if ($todo->isInInbox()) {
            return;
        }

        throw ValidationException::withMessages([
            'todo' => __('todos.validation.inbox_todo'),
        ]);
    }

    private function validateTitle(string $title): void
    {
        Validator::make(
            ['title' => $title],
            ['title' => ['required', 'string', 'max:'.InboxCaptureTitle::MaxLength, new InboxCaptureTitle]],
            [
                'title.required' => __('todos.validation.inbox_capture_title'),
                'title.string' => __('todos.validation.inbox_capture_title'),
                'title.max' => __('todos.validation.inbox_capture_title'),
            ],
            ['title' => __('todos.fields.title')],
        )->validate();
    }
}
