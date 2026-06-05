<?php

namespace App\Data\Setup;

final readonly class SetupStatusReport
{
    /**
     * @param  list<array{key: string, ok: bool, value: string, value_key?: string}>  $checks
     * @param  list<string>  $pendingMigrations
     */
    public function __construct(
        public bool $ready,
        public array $checks,
        public array $pendingMigrations,
        public ?string $databaseError,
    ) {}

    /**
     * @return array{ready: bool, checks: list<array{key: string, ok: bool, value: string, value_key?: string}>, pending_migrations: list<string>, database_error: string|null}
     */
    public function toArray(): array
    {
        return [
            'ready' => $this->ready,
            'checks' => $this->checks,
            'pending_migrations' => $this->pendingMigrations,
            'database_error' => $this->databaseError,
        ];
    }
}
