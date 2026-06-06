<?php

namespace App\Actions\Automation;

use App\Actions\Automation\Processes\ArchiveCompletedTasksProcess;
use App\Actions\Automation\Processes\PromoteOverdueTasksProcess;
use App\Actions\Processing\RunManualWebProcess;
use App\Contracts\Processing\ManualWebProcess;
use App\Data\Processing\ManualWebProcessResult;
use App\Enums\AutomationRuleKind;
use App\Enums\AutomationRunStatus;
use App\Models\AutomationRule;
use App\Models\AutomationRuleRun;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Runs a bounded, user-clicked automation chunk and records the result.
 *
 * The action never dispatches a job or assumes cron. If more records match than
 * the configured chunk size, the run stores the remaining count and the user
 * can click "Run now" again to resume from the next owner-scoped query result.
 */
final class RunAutomationRule
{
    public function __construct(
        private readonly RunManualWebProcess $runManualWebProcess,
        private readonly PromoteOverdueTasksProcess $promoteOverdueTasks,
        private readonly ArchiveCompletedTasksProcess $archiveCompletedTasks,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function handle(User $user, AutomationRule $automationRule, bool $dryRun = false): AutomationRuleRun
    {
        Gate::forUser($user)->authorize('run', $automationRule);

        $startedAt = now();

        if (! $automationRule->is_enabled) {
            return $this->storeRun(
                user: $user,
                automationRule: $automationRule,
                status: AutomationRunStatus::Disabled,
                dryRun: $dryRun,
                result: new ManualWebProcessResult(
                    processKey: $automationRule->kind->value,
                    matchedCount: 0,
                    processedCount: 0,
                    changedCount: 0,
                    skippedCount: 0,
                    details: [],
                    startedAt: $startedAt,
                    finishedAt: now(),
                ),
                message: __('automation.runs.messages.disabled'),
            );
        }

        try {
            $result = $this->runManualWebProcess->handle(
                user: $user,
                process: $this->processFor($automationRule),
                dryRun: $dryRun,
            );

            return $this->storeRun(
                user: $user,
                automationRule: $automationRule,
                status: AutomationRunStatus::Completed,
                dryRun: $dryRun,
                result: $result,
                message: __('automation.runs.messages.completed'),
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->storeRun(
                user: $user,
                automationRule: $automationRule,
                status: AutomationRunStatus::Failed,
                dryRun: $dryRun,
                result: new ManualWebProcessResult(
                    processKey: $automationRule->kind->value,
                    matchedCount: 0,
                    processedCount: 0,
                    changedCount: 0,
                    skippedCount: 0,
                    details: [],
                    startedAt: $startedAt,
                    finishedAt: now(),
                ),
                message: __('automation.runs.messages.failed'),
            );
        }
    }

    private function processFor(AutomationRule $automationRule): ManualWebProcess
    {
        return match ($automationRule->kind) {
            AutomationRuleKind::PromoteOverdueTasks => $this->promoteOverdueTasks,
            AutomationRuleKind::ArchiveCompletedTasks => $this->archiveCompletedTasks->forDays($this->archiveDays($automationRule)),
        };
    }

    private function archiveDays(AutomationRule $automationRule): int
    {
        $days = $automationRule->settings['days'] ?? AutomationRuleKind::ArchiveCompletedTasks->defaultSettings()['days'];

        if (! is_numeric($days)) {
            return 7;
        }

        return max(1, min(365, (int) $days));
    }

    private function storeRun(
        User $user,
        AutomationRule $automationRule,
        AutomationRunStatus $status,
        bool $dryRun,
        ManualWebProcessResult $result,
        string $message,
    ): AutomationRuleRun {
        $run = $user->automationRuleRuns()->create([
            'automation_rule_id' => $automationRule->id,
            'status' => $status,
            'dry_run' => $dryRun,
            'matched_count' => $result->matchedCount,
            'changed_count' => $result->changedCount,
            'skipped_count' => $result->skippedCount,
            'details' => $result->detailsFor('tasks'),
            'message' => $message,
            'started_at' => $result->startedAt,
            'finished_at' => $result->finishedAt,
        ]);

        $automationRule->forceFill([
            'last_run_at' => $result->finishedAt,
            'last_status' => $status,
            'last_message' => $message,
        ])->save();

        return $run;
    }
}
