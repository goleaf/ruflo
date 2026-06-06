<?php

namespace App\Actions\Reminders\Processes;

use App\Contracts\Processing\ManualWebProcess;
use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\User;
use App\Notifications\TodoReminderDueNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final class ProcessDueRemindersProcess implements ManualWebProcess
{
    public int $processedCount = 0;

    public int $skippedCount = 0;

    public int $failedCount = 0;

    public function key(): string
    {
        return 'reminders.process_due';
    }

    /**
     * @return Builder<Model>
     */
    public function query(User $user): Builder
    {
        /** @var Builder<Model> $query */
        $query = $user->reminders()
            ->getQuery()
            ->due()
            ->with(['todo' => fn ($query) => $query->withTrashed()->where('todos.user_id', $user->id)])
            ->orderBy('remind_at')
            ->orderBy('id');

        return $query;
    }

    public function process(User $user, Model $record): bool
    {
        if (! $record instanceof Reminder) {
            return false;
        }

        try {
            if (! $user->reminders_enabled) {
                $this->skip($record, 'preferences_disabled');

                return true;
            }

            if (! $record->isTaskActionable()) {
                $this->skip($record, 'task_not_actionable');

                return true;
            }

            $user->notify(new TodoReminderDueNotification($record));

            $record->forceFill([
                'status' => ReminderStatus::Processed,
                'processed_at' => now(),
                'skipped_at' => null,
                'skipped_reason' => null,
                'last_error' => null,
            ])->save();

            $this->processedCount++;

            return true;
        } catch (Throwable $exception) {
            report($exception);

            $this->skip($record, 'processing_failed', __('reminders.processing.skipped.processing_failed'), failed: true);

            return true;
        }
    }

    /**
     * @return array{id: int, title: string, status: string, message: string}
     */
    public function detail(Model $record): array
    {
        $todo = $record instanceof Reminder ? $record->todo : null;
        $status = $record instanceof Reminder ? $record->status : ReminderStatus::Skipped;

        return [
            'id' => (int) $record->getKey(),
            'title' => $todo instanceof Todo ? $todo->title : __('reminders.processing.unknown_task'),
            'status' => $status->label(),
            'message' => $record instanceof Reminder ? $this->messageFor($record) : __('reminders.processing.skipped.processing_failed'),
        ];
    }

    private function skip(Reminder $reminder, string $reason, ?string $error = null, bool $failed = false): void
    {
        $reminder->forceFill([
            'status' => ReminderStatus::Skipped,
            'processed_at' => null,
            'skipped_at' => now(),
            'skipped_reason' => $reason,
            'last_error' => $error,
        ])->save();

        $failed ? $this->failedCount++ : $this->skippedCount++;
    }

    private function messageFor(Reminder $reminder): string
    {
        if ($reminder->status === ReminderStatus::Processed) {
            return __('reminders.processing.processed');
        }

        return match ($reminder->skipped_reason) {
            'preferences_disabled' => __('reminders.processing.skipped.preferences_disabled'),
            'task_not_actionable' => __('reminders.processing.skipped.task_not_actionable'),
            'processing_failed' => __('reminders.processing.skipped.processing_failed'),
            default => __('reminders.processing.skipped.generic'),
        };
    }
}
