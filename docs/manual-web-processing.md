# Manual Web Processing

Step 053 adds the reusable manual web-processing engine for long operations.

The engine lives in:

- `App\Contracts\Processing\ManualWebProcess`
- `App\Actions\Processing\RunManualWebProcess`
- `App\Data\Processing\ManualWebProcessResult`

## Runtime Contract

Processors are authenticated web actions. They do not dispatch jobs, require
cron, require queue workers, call Artisan, or assume terminal access.

Each processor must provide:

- an owner-scoped query,
- a single-record mutation,
- a sanitized detail row for visible progress,
- a stable process key for reporting.

`RunManualWebProcess` handles the shared mechanics:

- counts matching owner-scoped records,
- processes at most the configured chunk size,
- stops before the configured request work window,
- supports dry runs without mutation,
- truncates visible detail rows,
- returns matched, processed, changed, remaining, detail, and timestamp data.

Retry and resume are explicit browser actions. If `skippedCount` is greater
than zero, the user can run the same Livewire action again. The next run
re-queries the remaining owner-scoped records instead of relying on a queue,
worker, cron tick, or resume token.

## Configuration

The defaults live in `config/hosting.php` and `.env.example`:

- `RUFLO_WEB_PROCESSING_CHUNK_SIZE=25`
- `RUFLO_WEB_PROCESSING_MAX_RUNTIME_SECONDS=8`
- `RUFLO_WEB_PROCESSING_RETRY_COOLDOWN_SECONDS=30`
- `RUFLO_WEB_PROCESSING_RESUME_AFTER_FAILURE=true`
- `RUFLO_WEB_PROCESSING_DETAIL_LIMIT=10`

`App\Data\Hosting\WebProcessingProfile` exposes bounded accessors so processors
cannot accidentally request unbounded work from a browser action.

## Current Consumer

Step 052 automation rules now use the reusable engine through small process
adapters:

- `App\Actions\Automation\Processes\PromoteOverdueTasksProcess`
- `App\Actions\Automation\Processes\ArchiveCompletedTasksProcess`

`RunAutomationRule` remains responsible for automation authorization, disabled
rule handling, error handling, and storing `automation_rule_runs`. The chunking,
dry-run, detail limit, and remaining-count logic now belongs to the generic
engine.

## Maintenance Visibility

The protected maintenance center displays the active manual web-processing
engine, chunk size, request work window, retry cooldown, resume setting, and
detail row limit. This keeps the restricted-hosting runtime visible without
terminal access.

## Privacy

The engine does not scope data by itself because different features have
different work queries. Every `ManualWebProcess::query()` implementation must
start from the authenticated user or another policy-approved owner boundary.
The current automation processes use `$user->todos()` and existing todo actions
so foreign records cannot be mutated or listed in progress details.
