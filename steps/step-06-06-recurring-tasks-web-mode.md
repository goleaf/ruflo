# STEP 06 — Recurring tasks, repeat rules, generated occurrences, exceptions, and safe recurrence lifecycle

## Global rules

Before this step, read:

- `../GLOBAL_RULES.md`
- repository progress files
- previous step results

## Step prompt

Add recurring tasks without cron.

Use on-demand generation when dashboard/task list/today/upcoming views open or when user clicks process button.

Generation must be idempotent, limited, duplicate-safe, ownership-safe, and timeout-safe.

Support/prepare daily, weekly, monthly, custom repeat rules, skip occurrence, edit occurrence, edit series, archive/restore/delete recurrence behavior.

Required output:
- recurring rules
- generated occurrence strategy
- web-triggered chunked generation
- duplicate prevention
- reminder integration
- tests
- docs/changelog/progress update
- commit


## Mandatory step checklist

- Inspect current project state.
- Respect Laravel 13 + Livewire + Flux + Tailwind 4.
- Do not use Volt.
- Respect restricted hosting mode.
- Use web UI instead of cron/jobs/artisan.
- Add/update factories and seeders where relevant.
- Add/update request classes and custom validation rules where relevant.
- Translate all visible text and validation messages.
- Add/update tests.
- Update documentation.
- Update changelog.
- Update progress files.
- Review git diff.
- Commit with a meaningful message.

## Final step response format

At the end of this step, report:

- what was implemented
- what was changed
- what tests/checks were run
- what documentation was updated
- what remains unfinished
- what risks remain
- next recommended step
