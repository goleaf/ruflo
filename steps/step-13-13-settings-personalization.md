# STEP 13 — User preferences, workspace settings, language, timezone, dashboard customization, and defaults

## Global rules

Before this step, read:

- `../GLOBAL_RULES.md`
- repository progress files
- previous step results

## Step prompt

Add settings and personalization.

Support/prepare:
- personal preferences
- workspace settings
- language
- timezone
- date/time format
- notification preferences
- reminder defaults
- dashboard customization
- task list preferences
- default task behavior
- privacy/collaboration settings

Settings must actually affect behavior. No decorative toggles.

Required output:
- settings UI using Livewire + Flux
- authorization for workspace settings
- validation request/rules
- localization
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
