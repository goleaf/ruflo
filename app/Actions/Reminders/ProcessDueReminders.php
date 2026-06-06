<?php

namespace App\Actions\Reminders;

use App\Actions\Processing\RunManualWebProcess;
use App\Actions\Reminders\Processes\ProcessDueRemindersProcess;
use App\Data\Reminders\ReminderProcessingResult;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class ProcessDueReminders
{
    public function __construct(
        private readonly RunManualWebProcess $runManualWebProcess,
    ) {}

    public function handle(User $user): ReminderProcessingResult
    {
        Gate::forUser($user)->authorize('process', Reminder::class);

        $process = new ProcessDueRemindersProcess;
        $result = $this->runManualWebProcess->handle($user, $process);

        return ReminderProcessingResult::fromManualResult(
            result: $result,
            processedCount: $process->processedCount,
            skippedCount: $process->skippedCount,
            failedCount: $process->failedCount,
        );
    }
}
