# STEP 14 — UI/UX polish, accessibility, responsive design, states, and final design cleanup

## Global rules

Before this step, read:

- `../GLOBAL_RULES.md`
- repository progress files
- previous step results

## Step prompt

Polish the whole interface.

Review and improve:
- dashboard
- task lists/forms/detail
- projects/tags/filters/bulk actions
- reminders/recurrence
- activity
- collaboration/invites
- comments
- attachments
- import/export
- settings
- login demo users panel

Use Flux consistently. Tailwind 4 for layout/utilities. SCSS only for clean reusable support.

Add empty/loading/error/success states, confirmations, mobile responsiveness, keyboard navigation, focus states, labels, contrast, translated microcopy.

Required output:
- consistent Flux UI
- responsive/mobile cleanup
- accessibility basics
- no hardcoded text
- tests/build
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
