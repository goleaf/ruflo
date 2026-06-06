# Automation Rules

Step 052 adds browser-triggered automation rules at `/todos/automations`.

Automation rules are private workspace records. Every rule and run log is owned
by one user, resolved through owner-scoped queries, and authorized through
policy checks before it can be viewed, toggled, tested, or run.

## Web-only processing

Rules do not use cron, queue workers, supervisors, shell access, terminal
commands, Artisan commands, paid services, or hosted automation providers.

The current rule runner processes one bounded chunk per Livewire action. The
chunk size comes from `hosting.web_processing.chunk_size` and is capped in the
application layer. When more records match than the current chunk, the run log
stores the remaining count. Clicking **Run now** again retries the same rule and
resumes from the next owner-scoped query result.

## Built-in rules

- **Promote overdue tasks** finds active overdue low or normal priority tasks
  and raises them to high priority.
- **Archive completed tasks** finds completed tasks older than seven days and
  moves them to the archive.

Both rules reuse existing todo lifecycle actions instead of mutating lifecycle
state directly from the Livewire component.

## Run logs

Every test or live run creates an `automation_rule_runs` record with:

- status,
- dry-run flag,
- matched count,
- changed count,
- remaining count,
- bounded task details for the owner,
- start and finish timestamps.

Disabled rules record a disabled run and change nothing. Failed runs store a
translated generic failure message so internal exception details are not exposed
to users.

## Validation

Rule names use `App\Rules\Automation\AutomationRuleName` and owner-scoped unique
validation. The workflow is Livewire-only, so Step 052 does not introduce an
HTTP controller or Form Request.
