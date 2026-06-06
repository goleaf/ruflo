# Restricted Hosting Mode

## Goal

RuFlo must work through browser UI on shared hosting where the owner may not have SSH, cron, queue workers, supervisors, shell scripts, or normal Artisan access.

## Runtime Defaults

- `QUEUE_CONNECTION=sync` is the default. Features must not require a queue worker for normal use.
- `RUFLO_RESTRICTED_HOSTING=true` is the default.
- `composer run dev` only starts Vite because Laravel Herd serves the site locally.
- `routes/console.php` intentionally has no application command workflow.

## Web Processing Contract

Long operations must be built as authenticated Livewire flows:

- process bounded chunks,
- show progress,
- store enough state to retry or resume,
- stop before request timeouts,
- make retries explicit,
- never depend on cron or workers for required behavior.

Current defaults live in `config/hosting.php` and are exposed through `App\Data\Hosting\WebProcessingProfile`:

- chunk size: 25 items,
- max request work window: 8 seconds,
- retry cooldown: 30 seconds,
- resume after failure: enabled,
- visible detail rows: 10 rows.

## What Is Not Promised

Without cron or workers, RuFlo cannot promise exact-time automation. Reminder, recurrence, cleanup, import, export, maintenance, and automation features must run when a user visits a relevant page or presses an authenticated processing button.

## Related Steps

Step 007 added the protected setup status foundation. Step 008 added the protected maintenance center. Step 052 added feature-specific browser-triggered automation rule chunks. Step 053 added the reusable manual web-processing engine that feature-specific processors can reuse. Step 054 adds reminder processing as another authenticated web-triggered consumer. Step 055 adds database-backed in-app notification review and read-state controls. Step 056 adds the daily summary dashboard as an authenticated browser-rendered replacement for scheduled summary email. Step 057 adds recurrence rule management only; generation remains a later web-triggered step.

## 2026-06-06 Recheck

Step 006 was rechecked from the root prompt pack and `steps/step-006-restricted-hosting-web-only-mode.md`.

Confirmed:

- Normal runtime still defaults to `QUEUE_CONNECTION=sync`.
- `RUFLO_RESTRICTED_HOSTING=true` and the web-processing knobs are present in `.env.example`.
- `config/hosting.php` exposes the forbidden runtime dependency list and chunk/retry/resume defaults.
- `App\Data\Hosting\WebProcessingProfile` resolves the configured web-processing profile.
- `routes/console.php` defines no application console workflow.
- `app/Jobs` does not exist.
- Local `composer run dev` starts Vite only; Herd serves `https://ruflo.test`.
- Setup and maintenance status surfaces expose the restricted-hosting profile through authenticated web UI.

## 2026-06-06 Step 052 Update

Automation rules run from `/todos/automations` through authenticated Livewire
actions. Each run processes a bounded owner-scoped chunk, stores matched,
changed, and remaining counts, and can be retried by clicking the same browser
action again. No cron, queue worker, supervisor, shell access, Artisan command,
terminal dependency, paid service, or hosted automation provider is required
during normal usage.

## 2026-06-06 Step 053 Update

`App\Actions\Processing\RunManualWebProcess` is the shared browser-triggered
processing engine. Features implement `App\Contracts\Processing\ManualWebProcess`
with an owner-scoped query, one-record mutation, and sanitized detail rows.

The engine applies the configured chunk size, request work window, and detail
limit, supports dry runs without mutation, and returns matched, processed,
changed, and remaining counts for Livewire progress reports. Retry and resume
remain explicit user actions: click the same web action again to process the
next owner-scoped chunk.

## 2026-06-06 Step 054 Update

Reminders process from dashboard opens, `/todos/reminders` page opens, and the
manual Process due button. Each run uses `RunManualWebProcess` to process a
bounded owner-scoped chunk, then reports matched, processed, skipped, failed,
and remaining counts in Livewire.

Reminder delivery is self-hosted database notifications only. There is no cron,
queue worker, supervisor, shell access, Artisan command, email dependency, paid
service, or hosted notification provider required for normal reminder usage.

## 2026-06-06 Step 055 Update

The notification center reads and updates existing database notifications from
the authenticated browser session. Read/unread changes, filtering, and
pagination are synchronous web requests and require no cron, queue worker,
supervisor, shell access, Artisan command, email provider, push provider, or
paid service during normal usage.

## 2026-06-06 Step 056 Update

The daily summary dashboard is computed when an authenticated user opens
`/dashboard`. It displays owner-scoped due-today, overdue, blocked, reminder,
notification, and tracked-time counters through normal Livewire rendering.

No scheduled email, cron, queue worker, supervisor, shell access, Artisan
command, external notification provider, hosted reporting service, or paid API
is required for normal daily-summary usage.

## 2026-06-06 Step 057 Update

Recurring task rules are created, edited, paused, enabled, and deleted through
authenticated Livewire browser requests on `/todos/recurring` and task detail
pages. Step 057 stores rule definitions and summaries only.

No recurrence scheduler, cron, queue worker, supervisor, shell access, Artisan
command, external calendar service, email provider, hosted automation provider,
or paid API is required for normal rule management. The
`last_generated_until` column is reserved for the next web-triggered occurrence
generation step so future chunks can remain idempotent and resumable.
