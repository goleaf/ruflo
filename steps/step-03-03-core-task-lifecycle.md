# STEP 03 — Core Todo task lifecycle, safe actions, validation, and predictable behavior

## Global rules

Before this step, read:

- `../GLOBAL_RULES.md`
- repository progress files
- previous step results

## Step prompt

Build the core task lifecycle.

Support create, view, edit, complete, reopen, archive, restore, and delete behavior for own tasks.

Completion is not deletion. Archive is not deletion. Restore is not duplication. Delete is intentional.

Every action must be authorized, validated, translated, tested, documented, and implemented through modern Livewire + Flux UI where appropriate.

Required output:
- task lifecycle
- safe state transitions
- request classes/rules
- Livewire task UI
- Flux forms/buttons/modals
- seed/demo states
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
