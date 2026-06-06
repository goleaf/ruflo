<?php

namespace App\Data\Hosting;

final readonly class WebProcessingProfile
{
    /**
     * @param  list<string>  $forbiddenRuntimeDependencies
     */
    public function __construct(
        public bool $restricted,
        public int $chunkSize,
        public int $maxRuntimeSeconds,
        public int $retryCooldownSeconds,
        public bool $resumeAfterFailure,
        public int $detailLimit,
        public array $forbiddenRuntimeDependencies,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            restricted: (bool) config('hosting.restricted', true),
            chunkSize: (int) config('hosting.web_processing.chunk_size', 25),
            maxRuntimeSeconds: (int) config('hosting.web_processing.max_runtime_seconds', 8),
            retryCooldownSeconds: (int) config('hosting.web_processing.retry_cooldown_seconds', 30),
            resumeAfterFailure: (bool) config('hosting.web_processing.resume_after_failure', true),
            detailLimit: (int) config('hosting.web_processing.detail_limit', 10),
            forbiddenRuntimeDependencies: array_values(config('hosting.forbidden_runtime_dependencies', [])),
        );
    }

    public function shouldChunk(): bool
    {
        return $this->chunkSize > 0 && $this->maxRuntimeSeconds > 0;
    }

    public function boundedChunkSize(): int
    {
        return max(1, min(100, $this->chunkSize));
    }

    public function boundedMaxRuntimeSeconds(): int
    {
        return max(1, min(30, $this->maxRuntimeSeconds));
    }

    public function boundedDetailLimit(): int
    {
        return max(0, min(50, $this->detailLimit));
    }
}
