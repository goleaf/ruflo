# STEP 04 — Task organization, projects/lists, tags, priorities, due dates, search, filters, sorting, and bulk actions

## Global rules

Before this step, read:

- `../GLOBAL_RULES.md`
- repository progress files
- previous step results

## Step prompt

Add organization and productivity control.

Implement/prepare projects/lists, tags, priorities, due dates, today/overdue/upcoming views, search, filters, sorting, and bulk actions.

Everything must respect ownership and collaboration scope when later added.

Bulk actions must validate every selected item and authorize every affected resource.

Required output:
- organization layer
- search/filter/sort
- safe bulk actions
- Livewire interactive task list
- Flux filter panels/badges/modals
- realistic seed data
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
