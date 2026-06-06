<?php

namespace App\Actions\Automation;

use App\Actions\Todos\ArchiveTodo;
use App\Actions\Todos\UpdateTodo;
use App\Data\Todos\TodoData;
use App\Enums\AutomationRuleKind;
use App\Enums\AutomationRunStatus;
use App\Enums\Priority;
use App\Models\AutomationRule;
use App\Models\AutomationRuleRun;
use App\Models\Todo;
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
    private const DetailLimit = 10;

    public function __construct(
        private readonly UpdateTodo $updateTodo,
        private readonly ArchiveTodo $archiveTodo,
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
                matchedCount: 0,
                changedCount: 0,
                skippedCount: 0,
                details: [],
                message: __('automation.runs.messages.disabled'),
                startedAt: $startedAt,
            );
        }

        try {
            $result = match ($automationRule->kind) {
                AutomationRuleKind::PromoteOverdueTasks => $this->promoteOverdueTasks($user, $dryRun),
                AutomationRuleKind::ArchiveCompletedTasks => $this->archiveCompletedTasks($user, $automationRule, $dryRun),
            };

            return $this->storeRun(
                user: $user,
                automationRule: $automationRule,
                status: AutomationRunStatus::Completed,
                dryRun: $dryRun,
                matchedCount: $result['matched'],
                changedCount: $result['changed'],
                skippedCount: $result['skipped'],
                details: $result['details'],
                message: __('automation.runs.messages.completed'),
                startedAt: $startedAt,
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->storeRun(
                user: $user,
                automationRule: $automationRule,
                status: AutomationRunStatus::Failed,
                dryRun: $dryRun,
                matchedCount: 0,
                changedCount: 0,
                skippedCount: 0,
                details: [],
                message: __('automation.runs.messages.failed'),
                startedAt: $startedAt,
            );
        }
    }

    /**
     * @return array{matched: int, changed: int, skipped: int, details: array{tasks: list<array{id: int, title: string}>}}
     */
    private function promoteOverdueTasks(User $user, bool $dryRun): array
    {
        $query = $user->todos()
            ->with('tags:id')
            ->active()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->whereIn('priority', [Priority::Low->value, Priority::Normal->value])
            ->orderBy('due_date')
            ->orderBy('id');

        $matchedCount = (clone $query)->count();
        $todos = $query->limit($this->chunkSize())->get();
        $changedCount = 0;
        $details = [];

        foreach ($todos as $todo) {
            if (! $todo instanceof Todo) {
                continue;
            }

            $details[] = $this->detailFor($todo);

            if ($dryRun) {
                continue;
            }

            $this->updateTodo->handle($user, $todo, new TodoData(
                title: $todo->title,
                priority: Priority::High,
                dueDate: $todo->due_date?->toDateString(),
                projectId: $todo->project_id,
                tagIds: $todo->tags->pluck('id')->map(fn (int|string $id): int => (int) $id)->all(),
            ));

            $changedCount++;
        }

        return $this->result($matchedCount, $changedCount, $todos->count(), $details);
    }

    /**
     * @return array{matched: int, changed: int, skipped: int, details: array{tasks: list<array{id: int, title: string}>}}
     */
    private function archiveCompletedTasks(User $user, AutomationRule $automationRule, bool $dryRun): array
    {
        $days = $this->archiveDays($automationRule);

        $query = $user->todos()
            ->completed()
            ->where('updated_at', '<=', now()->subDays($days))
            ->orderBy('updated_at')
            ->orderBy('id');

        $matchedCount = (clone $query)->count();
        $todos = $query->limit($this->chunkSize())->get();
        $changedCount = 0;
        $details = [];

        foreach ($todos as $todo) {
            if (! $todo instanceof Todo) {
                continue;
            }

            $details[] = $this->detailFor($todo);

            if ($dryRun) {
                continue;
            }

            $this->archiveTodo->handle($todo);
            $changedCount++;
        }

        return $this->result($matchedCount, $changedCount, $todos->count(), $details);
    }

    /**
     * @param  list<array{id: int, title: string}>  $details
     * @return array{matched: int, changed: int, skipped: int, details: array{tasks: list<array{id: int, title: string}>}}
     */
    private function result(int $matchedCount, int $changedCount, int $processedCount, array $details): array
    {
        return [
            'matched' => $matchedCount,
            'changed' => $changedCount,
            'skipped' => max(0, $matchedCount - $processedCount),
            'details' => [
                'tasks' => array_slice($details, 0, self::DetailLimit),
            ],
        ];
    }

    /**
     * @return array{id: int, title: string}
     */
    private function detailFor(Todo $todo): array
    {
        return [
            'id' => $todo->id,
            'title' => $todo->title,
        ];
    }

    private function archiveDays(AutomationRule $automationRule): int
    {
        $days = $automationRule->settings['days'] ?? AutomationRuleKind::ArchiveCompletedTasks->defaultSettings()['days'];

        if (! is_numeric($days)) {
            return 7;
        }

        return max(1, min(365, (int) $days));
    }

    private function chunkSize(): int
    {
        return max(1, min(100, (int) config('hosting.web_processing.chunk_size', 25)));
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function storeRun(
        User $user,
        AutomationRule $automationRule,
        AutomationRunStatus $status,
        bool $dryRun,
        int $matchedCount,
        int $changedCount,
        int $skippedCount,
        array $details,
        string $message,
        mixed $startedAt,
    ): AutomationRuleRun {
        $finishedAt = now();

        $run = $user->automationRuleRuns()->create([
            'automation_rule_id' => $automationRule->id,
            'status' => $status,
            'dry_run' => $dryRun,
            'matched_count' => $matchedCount,
            'changed_count' => $changedCount,
            'skipped_count' => $skippedCount,
            'details' => $details,
            'message' => $message,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
        ]);

        $automationRule->forceFill([
            'last_run_at' => $finishedAt,
            'last_status' => $status,
            'last_message' => $message,
        ])->save();

        return $run;
    }
}
