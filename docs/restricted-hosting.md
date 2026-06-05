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
- resume after failure: enabled.

## What Is Not Promised

Without cron or workers, RuFlo cannot promise exact-time automation. Reminder, recurrence, cleanup, import, export, and maintenance features must run when a user visits a relevant page or presses an authenticated processing button.

## Related Steps

Step 007 added the protected setup status foundation. Step 008 added the protected maintenance center. Step 053 remains responsible for the generic manual web processing engine that feature-specific processors can reuse.

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
