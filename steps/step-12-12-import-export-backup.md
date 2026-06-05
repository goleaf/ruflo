# STEP 12 — Import, export, backup, data portability, duplicate handling, and privacy-safe data movement

## Global rules

Before this step, read:

- `../GLOBAL_RULES.md`
- repository progress files
- previous step results

## Step prompt

Add safe import/export without queues/workers.

Use Livewire web wizards:
- upload
- validate
- preview
- duplicate detection
- confirm
- chunked process
- progress
- retry/resume
- final report

Exports must be private, scoped, protected, expirable/cleanable via web maintenance.

Prevent CSV formula injection and ownership spoofing.

Required output:
- CSV/JSON export
- safe import
- preview and duplicate handling
- private generated files
- chunked web processing
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
