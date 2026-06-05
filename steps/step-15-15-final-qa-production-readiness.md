# STEP 15 — Final testing, security review, performance review, CI/checks, release checklist, and production readiness

## Global rules

Before this step, read:

- `../GLOBAL_RULES.md`
- repository progress files
- previous step results

## Step prompt

Final QA only. Do not add random new features.

Review:
- tests
- privacy
- authorization
- collaboration roles
- task lifecycle
- reminders
- recurrence
- dashboard accuracy
- activity
- comments
- attachments/storage
- import/export
- settings
- UI/UX
- accessibility
- translations
- performance
- restricted hosting mode
- web maintenance tools
- docs/changelog/release checklist

Fix critical issues. Document remaining risks honestly.

Required output:
- full QA report
- security/privacy review
- performance review
- release checklist
- production readiness status
- final docs/changelog/progress update
- final commit


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
