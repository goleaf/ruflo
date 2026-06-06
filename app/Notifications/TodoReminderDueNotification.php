<?php

namespace App\Notifications;

use App\Models\Reminder;
use App\Models\Todo;
use Illuminate\Notifications\Notification;

final class TodoReminderDueNotification extends Notification
{
    public function __construct(
        private readonly Reminder $reminder,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'todo-reminder-due';
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $todo = $this->reminder->todo;

        return [
            'kind' => 'todo_reminder_due',
            'reminder_id' => $this->reminder->id,
            'todo_id' => $todo instanceof Todo ? $todo->id : null,
            'title' => __('reminders.notifications.todo_due.title'),
            'message' => __('reminders.notifications.todo_due.message', [
                'task' => $todo instanceof Todo ? $todo->title : __('reminders.processing.unknown_task'),
            ]),
            'action_url' => $todo instanceof Todo ? route('todos.show', $todo) : route('todos.index'),
            'remind_at' => $this->reminder->remind_at?->toIso8601String(),
        ];
    }
}
