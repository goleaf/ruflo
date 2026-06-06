<?php

use App\Actions\Reminders\ProcessDueReminders;
use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Carbon;

test('due reminder notifications are database only and link back through authorized task routes', function () {
    Carbon::setTestNow('2026-03-10 09:00:00');

    try {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $todo = Todo::factory()->for($user)->active()->create(['title' => 'Review reminder payload']);
        $foreignTodo = Todo::factory()->for($other)->active()->create(['title' => 'Foreign payload']);
        $reminder = Reminder::factory()->forTodo($todo)->due('2026-03-10 08:00:00')->create();
        Reminder::factory()->forTodo($foreignTodo)->due('2026-03-10 08:00:00')->create();

        $result = app(ProcessDueReminders::class)->handle($user);

        $notification = $user->notifications()->sole();

        expect($result->processedCount)->toBe(1)
            ->and($reminder->refresh()->status)->toBe(ReminderStatus::Processed)
            ->and($notification->type)->toBe('todo-reminder-due')
            ->and($notification->data['kind'])->toBe('todo_reminder_due')
            ->and($notification->data['reminder_id'])->toBe($reminder->id)
            ->and($notification->data['todo_id'])->toBe($todo->id)
            ->and($notification->data['title'])->toBe(__('reminders.notifications.todo_due.title'))
            ->and($notification->data['message'])->toContain('Review reminder payload')
            ->and($notification->data['action_url'])->toBe(route('todos.show', $todo))
            ->and($other->notifications()->count())->toBe(0);
    } finally {
        Carbon::setTestNow();
    }
});
