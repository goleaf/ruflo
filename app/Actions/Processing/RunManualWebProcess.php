<?php

namespace App\Actions\Processing;

use App\Contracts\Processing\ManualWebProcess;
use App\Data\Hosting\WebProcessingProfile;
use App\Data\Processing\ManualWebProcessResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class RunManualWebProcess
{
    public function handle(User $user, ManualWebProcess $process, bool $dryRun = false, ?WebProcessingProfile $profile = null): ManualWebProcessResult
    {
        $profile ??= WebProcessingProfile::fromConfig();
        $startedAt = now();
        $query = $process->query($user);
        $matchedCount = (clone $query)->count();
        $deadline = microtime(true) + $profile->boundedMaxRuntimeSeconds();
        $records = $query->limit($profile->boundedChunkSize())->get();
        $changedCount = 0;
        $processedCount = 0;
        $details = [];

        foreach ($records as $record) {
            if (! $record instanceof Model) {
                continue;
            }

            if ($processedCount > 0 && microtime(true) >= $deadline) {
                break;
            }

            if (! $dryRun && $process->process($user, $record)) {
                $changedCount++;
            }

            $details[] = $process->detail($record);
            $processedCount++;
        }

        return new ManualWebProcessResult(
            processKey: $process->key(),
            matchedCount: $matchedCount,
            processedCount: $processedCount,
            changedCount: $changedCount,
            skippedCount: max(0, $matchedCount - $processedCount),
            details: array_slice($details, 0, $profile->boundedDetailLimit()),
            startedAt: $startedAt,
            finishedAt: now(),
        );
    }
}
