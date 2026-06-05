# STEP 10 — Task comments, discussion, mentions, comment permissions, notifications, and moderation

## Global rules

Before this step, read:

- `../GLOBAL_RULES.md`
- repository progress files
- previous step results

## Step prompt

Add safe task discussion.

Support task comments, edit/delete rules, mention rules if implemented, comment notifications, comment activity, and moderation if useful.

Comments must follow ownership and collaboration roles. Removed members lose access. Mention suggestions must not leak private tasks/users.

Protect from XSS. Escape/sanitize user content.

Required output:
- task comments
- role-based comment permissions
- optional mentions
- comment notifications/preferences
- activity integration
- content safety tests
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
