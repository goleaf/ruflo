<?php

namespace App\Actions\Todos;

use App\Actions\Processing\RunManualWebProcess;
use App\Actions\Todos\Processes\GenerateRecurringOccurrencesProcess;
use App\Data\Todos\RecurrenceGenerationResult;
use App\Models\TodoRecurrenceRule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Gate;

final class GenerateRecurringOccurrences
{
    public function __construct(
        private readonly RunManualWebProcess $runManualWebProcess,
    ) {}

    public function handle(User $user, ?CarbonInterface $windowEnd = null): RecurrenceGenerationResult
    {
        Gate::forUser($user)->authorize('process', TodoRecurrenceRule::class);

        $windowEnd = CarbonImmutable::parse($windowEnd ?? today()->addWeeks(8))->endOfDay();
        $process = new GenerateRecurringOccurrencesProcess($windowEnd);
        $result = $this->runManualWebProcess->handle($user, $process);

        return RecurrenceGenerationResult::fromManualResult(
            result: $result,
            createdCount: $process->createdCount,
            skippedRuleCount: $process->skippedRuleCount,
            failedCount: $process->failedCount,
            windowEnd: $windowEnd,
        );
    }
}
