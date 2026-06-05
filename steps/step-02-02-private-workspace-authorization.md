# STEP 02 — Private workspace, ownership, authorization, and user access logic

## Global rules

Before this step, read:

- `../GLOBAL_RULES.md`
- repository progress files
- previous step results

## Step prompt

Build the access-control foundation.

Everything is private by default. Users see only their own workspace unless explicit sharing is added later.

Implement/prepare policies, ownership scoping, protected routes, safe query scopes, backend authorization, guest blocking, private dashboard access rules, multi-user privacy tests, and documentation.

No frontend-only security. No global task access. No guessed ID access.

Required output:
- private workspace logic
- ownership enforcement
- route protection
- policy structure
- query scoping rules
- multi-user tests
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
