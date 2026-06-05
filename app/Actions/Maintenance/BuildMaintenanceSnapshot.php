<?php

namespace App\Actions\Maintenance;

use App\Actions\Setup\InspectSetupStatus;
use App\Data\Hosting\WebProcessingProfile;
use Illuminate\Support\Facades\File;

class BuildMaintenanceSnapshot
{
    public function __construct(
        private readonly InspectSetupStatus $inspectSetupStatus,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $profile = WebProcessingProfile::fromConfig();
        $compiledViewFiles = File::glob(storage_path('framework/views/*.php')) ?: [];
        $setup = ($this->inspectSetupStatus)()->toArray();

        return [
            'setup' => $setup,
            'processing' => [
                'chunk_size' => $profile->chunkSize,
                'max_runtime_seconds' => $profile->maxRuntimeSeconds,
                'retry_cooldown_seconds' => $profile->retryCooldownSeconds,
                'resume_after_failure' => $profile->resumeAfterFailure,
            ],
            'runtime' => [
                'cache_store' => (string) config('cache.default'),
                'session_driver' => (string) config('session.driver'),
                'queue_connection' => (string) config('queue.default'),
                'compiled_views' => count($compiledViewFiles),
                'storage_writable' => is_writable(storage_path()) && is_writable(storage_path('framework')),
            ],
        ];
    }
}
