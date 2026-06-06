<?php

namespace App\Queries\Reminders;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class ReminderListQuery
{
    /**
     * @return Builder<Reminder>
     */
    public function for(User $user): Builder
    {
        return Reminder::query()
            ->ownedBy($user)
            ->with(['todo' => fn ($query) => $query->withTrashed()->where('todos.user_id', $user->id)])
            ->latest('remind_at')
            ->latest('id');
    }

    public function findFor(User $user, int $reminderId): Reminder
    {
        $reminder = $this->for($user)->find($reminderId);

        if ($reminder instanceof Reminder) {
            return $reminder;
        }

        throw (new ModelNotFoundException)->setModel(Reminder::class, [$reminderId]);
    }

    public function pendingForTodo(User $user, Todo $todo): ?Reminder
    {
        return Reminder::query()
            ->ownedBy($user)
            ->where('todo_id', $todo->id)
            ->pending()
            ->first();
    }

    /**
     * @return Collection<int, Reminder>
     */
    public function dueFor(User $user, int $limit): Collection
    {
        return Reminder::query()
            ->ownedBy($user)
            ->due()
            ->with(['todo' => fn ($query) => $query->withTrashed()])
            ->orderBy('remind_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Reminder>
     */
    public function visibleRecentFor(User $user, int $limit = 12): Collection
    {
        return $this->for($user)
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Reminder>
     */
    public function pendingForLocalNotifications(User $user, int $limit = 50): Collection
    {
        return Reminder::query()
            ->ownedBy($user)
            ->pending()
            ->whereNotNull('remind_at')
            ->whereHas('todo', fn ($query) => $query
                ->where('todos.user_id', $user->id)
                ->whereNull('todos.deleted_at')
                ->active())
            ->with(['todo' => fn ($query) => $query
                ->where('todos.user_id', $user->id)
                ->whereNull('todos.deleted_at')
                ->active()])
            ->orderBy('remind_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Todo>
     */
    public function taskOptionsFor(User $user): Collection
    {
        return Todo::query()
            ->select(['id', 'user_id', 'project_id', 'title', 'priority', 'due_date', 'is_completed', 'archived_at', 'deleted_at', 'created_at', 'updated_at'])
            ->ownedBy($user)
            ->active()
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->latest('updated_at')
            ->limit(80)
            ->get();
    }

    /**
     * @return array{pending: int, due: int, processed: int, skipped: int}
     */
    public function summaryFor(User $user): array
    {
        $now = now()->toDateTimeString();

        $summary = Reminder::query()
            ->ownedBy($user)
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as pending_count', [ReminderStatus::Pending->value])
            ->selectRaw('sum(case when status = ? and remind_at <= ? then 1 else 0 end) as due_count', [ReminderStatus::Pending->value, $now])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as processed_count', [ReminderStatus::Processed->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as skipped_count', [ReminderStatus::Skipped->value])
            ->first();

        return [
            'pending' => (int) $summary->pending_count,
            'due' => (int) $summary->due_count,
            'processed' => (int) $summary->processed_count,
            'skipped' => (int) $summary->skipped_count,
        ];
    }
}
