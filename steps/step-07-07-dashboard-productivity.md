# STEP 07 — Productivity dashboard, widgets, statistics, progress, and optimized private overview

## Global rules

Before this step, read:

- `../GLOBAL_RULES.md`
- repository progress files
- previous step results

## Step prompt

Build a useful dashboard.

Widgets:
- today tasks
- overdue tasks
- upcoming tasks
- high priority
- reminders
- recurring tasks
- project/list progress
- completion stats
- recent activity

Every widget must be private, scoped, fast, accurate, and translated. No fake statistics. No N+1 queries.

Required output:
- Livewire dashboard widgets
- Flux dashboard cards
- optimized scoped queries
- empty/loading states
- dashboard customization readiness
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
