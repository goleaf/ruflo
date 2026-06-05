# STEP 05 — Reminders, notifications, overdue alerts, daily summary, and web-triggered scheduling

## Global rules

Before this step, read:

- `../GLOBAL_RULES.md`
- repository progress files
- previous step results

## Step prompt

Add reminders and notifications without cron or workers.

Use web-triggered processing:
- when dashboard opens
- when notification panel opens
- manual maintenance/process button
- chunked Livewire processing

Prefer in-app notifications. Email must not be required.

Do not promise exact-time reminders. Document restricted hosting behavior.

Required output:
- reminder model/logic
- in-app notifications
- daily summary dashboard widget
- web-triggered reminder processing
- notification preferences
- no cron dependency
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
