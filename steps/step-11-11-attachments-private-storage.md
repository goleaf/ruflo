# STEP 11 — Attachments, uploads, private downloads, image validation, and storage security

## Global rules

Before this step, read:

- `../GLOBAL_RULES.md`
- repository progress files
- previous step results

## Step prompt

Add secure attachments.

Private files must never be publicly reachable. Downloads/previews/thumbnails must be authorization-protected.

Validate file type, size, extension, image safety, upload count, and scope. Reject dangerous files. No public storage leaks.

Attachments must follow task/comment/project/collaboration permissions.

Required output:
- task/comment attachments if chosen
- private storage
- protected downloads/previews
- upload validation
- image handling where safe
- orphan cleanup via web maintenance
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
