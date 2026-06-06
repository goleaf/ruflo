<?php

namespace App\Actions\Reminders;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Reminders\ReminderListQuery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SyncTodoReminder
{
    public function __construct(
        private readonly ReminderListQuery $reminders,
    ) {}

    public function handle(User $user, Todo $todo, Carbon $remindAt): Reminder
    {
        Gate::forUser($user)->authorize('create', Reminder::class);

        if (! $todo->isOwnedBy($user) || ! $todo->isActive() || $todo->trashed()) {
            throw ValidationException::withMessages([
                'todo_id' => __('reminders.validation.todo_actionable'),
            ]);
        }

        $reminder = Reminder::query()
            ->ownedBy($user)
            ->where('todo_id', $todo->id)
            ->first();

        if ($reminder instanceof Reminder) {
            Gate::forUser($user)->authorize('update', $reminder);
        } else {
            $reminder = $user->reminders()->make();
        }

        $reminder->forceFill([
            'todo_id' => $todo->id,
            'remind_at' => $remindAt,
            'status' => ReminderStatus::Pending,
            'processed_at' => null,
            'skipped_at' => null,
            'skipped_reason' => null,
            'last_error' => null,
        ])->save();

        return $reminder->refresh()->load('todo');
    }

    public function clear(User $user, Todo $todo): void
    {
        $reminder = $this->reminders->pendingForTodo($user, $todo);

        if (! $reminder instanceof Reminder) {
            return;
        }

        Gate::forUser($user)->authorize('delete', $reminder);

        $reminder->delete();
    }
}
