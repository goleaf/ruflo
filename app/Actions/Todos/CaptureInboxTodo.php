<?php

namespace App\Actions\Todos;

use App\Data\Todos\TodoData;
use App\Enums\Priority;
use App\Models\Todo;
use App\Models\User;
use App\Rules\Todos\InboxCaptureTitle;
use Illuminate\Support\Facades\Validator;

/**
 * Creates a lightweight task in the user's private inbox.
 *
 * This is intentionally synchronous and web-triggered: quick capture writes a
 * single owned todo and marks it for later triage. No queue, cron, worker, or
 * artisan command is involved in normal usage.
 */
final class CaptureInboxTodo
{
    public function __construct(
        private readonly CreateTodo $createTodo,
    ) {}

    public function handle(User $user, string $title): Todo
    {
        $normalizedTitle = $this->validatedTitle($title);

        $todo = $this->createTodo->handle($user, new TodoData(
            title: $normalizedTitle,
            priority: Priority::Normal,
        ));

        $todo->forceFill([
            'inbox_captured_at' => now(),
        ])->save();

        return $todo->refresh();
    }

    private function validatedTitle(string $title): string
    {
        Validator::make(
            ['title' => $title],
            ['title' => ['required', 'string', 'max:'.InboxCaptureTitle::MaxLength, new InboxCaptureTitle]],
            [
                'title.required' => __('todos.validation.inbox_capture_title'),
                'title.string' => __('todos.validation.inbox_capture_title'),
                'title.max' => __('todos.validation.inbox_capture_title'),
            ],
            ['title' => __('todos.inbox.fields.capture_title')],
        )->validate();

        return InboxCaptureTitle::normalize($title) ?? '';
    }
}
