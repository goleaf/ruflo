<?php

namespace App\Actions\Setup;

use App\Data\Setup\SetupStatusReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class InspectSetupStatus
{
    public function __invoke(): SetupStatusReport
    {
        $migrationFiles = $this->migrationFiles();
        $ranMigrations = [];
        $databaseConfigured = false;
        $migrationsTableExists = false;
        $databaseError = null;

        try {
            DB::connection()->getPdo();

            $databaseConfigured = true;
            $migrationsTableExists = Schema::hasTable('migrations');
            $ranMigrations = $migrationsTableExists
                ? DB::table('migrations')->pluck('migration')->all()
                : [];
        } catch (\Throwable) {
            $databaseError = 'unavailable';
        }

        $pendingMigrations = array_values(array_diff($migrationFiles, $ranMigrations));
        $appUrl = (string) config('app.url');
        $queueConnection = (string) config('queue.default');
        $restrictedHosting = (bool) config('hosting.restricted');
        $appKeyConfigured = filled(config('app.key'));
        $appUrlUsesHttps = str_starts_with($appUrl, 'https://');
        $storageWritable = is_writable(storage_path()) && is_writable(storage_path('framework'));

        $checks = [
            ['key' => 'app_key', 'ok' => $appKeyConfigured, 'value' => $appKeyConfigured ? 'configured' : 'missing', 'value_key' => $appKeyConfigured ? 'configured' : 'missing'],
            ['key' => 'app_url', 'ok' => $appUrlUsesHttps, 'value' => $appUrl],
            ['key' => 'database', 'ok' => $databaseConfigured, 'value' => $databaseConfigured ? 'connected' : 'unavailable', 'value_key' => $databaseConfigured ? 'connected' : 'unavailable'],
            ['key' => 'migrations_table', 'ok' => $migrationsTableExists, 'value' => $migrationsTableExists ? 'present' : 'missing', 'value_key' => $migrationsTableExists ? 'present' : 'missing'],
            ['key' => 'pending_migrations', 'ok' => $pendingMigrations === [], 'value' => (string) count($pendingMigrations)],
            ['key' => 'queue_connection', 'ok' => $queueConnection === 'sync', 'value' => $queueConnection, 'value_key' => in_array($queueConnection, ['sync', 'database'], true) ? $queueConnection : 'custom'],
            ['key' => 'restricted_hosting', 'ok' => $restrictedHosting, 'value' => $restrictedHosting ? 'enabled' : 'disabled', 'value_key' => $restrictedHosting ? 'enabled' : 'disabled'],
            ['key' => 'storage_writable', 'ok' => $storageWritable, 'value' => $storageWritable ? 'writable' : 'not_writable', 'value_key' => $storageWritable ? 'writable' : 'not_writable'],
        ];

        return new SetupStatusReport(
            ready: collect($checks)->every(fn (array $check): bool => $check['ok']),
            checks: $checks,
            pendingMigrations: $pendingMigrations,
            databaseError: $databaseError,
        );
    }

    /**
     * @return list<string>
     */
    private function migrationFiles(): array
    {
        return collect(File::files(database_path('migrations')))
            ->map(fn ($file): string => pathinfo($file->getFilename(), PATHINFO_FILENAME))
            ->sort()
            ->values()
            ->all();
    }
}
