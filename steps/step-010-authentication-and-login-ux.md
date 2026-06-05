# STEP 010 — Authentication and login UX

## Purpose

Review and polish auth/login UX, including safe local/testing demo user access.

This step is a separate real implementation task. Do not merge it into another step. Do not mark a range of later steps as completed. Do not write progress like `grouped future step range`. This exact step must have its own progress entry, notes, tests/checks, risks, and commit when stable.

## Global requirements for this step

Follow all rules from `../GLOBAL_RULES.md`.

The most important rules are:

- Use Laravel 13 conventions.
- Use latest Livewire for interactive behavior.
- Use Flux v2 as the UI component standard.
- Use Tailwind CSS 4.
- Use SCSS only as a clean supporting layer where useful.
- Do not use Volt.
- If touched code uses Volt, migrate it to normal class-based Livewire.
- Use only free/open-source/self-hosted/browser-native solutions.
- Support restricted hosting: no terminal, no cron, no workers, no supervisor, no artisan dependency for normal app usage.
- Use web-triggered Livewire workflows for long or scheduled operations.
- Use dedicated request classes and custom validation rules where appropriate.
- Translate all visible text and all validation/action messages in English language files.
- Keep private data private.
- Update tests, docs, changelog, and progress files.

## Before changing code, inspect

- current authentication routes, login view, password handling, middleware, session behavior, demo users, and production environment checks.
- current Laravel version, PHP requirement, installed packages, routes, middleware, auth stack, frontend build, tests, docs, translations, layouts, and components.
- existing architecture conventions so new code does not create a second style.
- places where old CRUD, hardcoded text, duplicated UI, or unsafe route logic already exists.

## Implementation tasks

- keep login clean and responsive.
- show demo credentials only in local/testing/demo.
- block demo credential panel in production.
- make login redirects use https://ruflo.test/ config.
- prepare a clear implementation plan before changes.
- place code according to Laravel conventions and existing project structure.
- make changes small enough to review and commit safely.

## Livewire and Flux UI requirements

- use Flux cards, inputs, buttons, alerts, and demo user list.
- make demo users selectable without exposing real user passwords.
- show username/email and fixed demo password only for seeded demo accounts.
- use Flux components for page shells, cards, forms, buttons, alerts, modals, tabs, badges, and empty states.
- use Livewire only where interactivity improves UX.
- keep mobile and desktop layouts consistent.

## Validation and request/rule requirements

- Create or update a dedicated request class for request-driven actions where this step introduces or changes input handling.
- Create or update custom validation rules for repeated business logic.
- Keep validation messages translated.
- Do not duplicate the same validation arrays in several controllers or Livewire components.
- Do not allow frontend-only validation to be the only protection.
- Preserve user input after validation failure where appropriate.
- Show errors close to the related Flux field.
- Translate field attributes and custom rule messages.
- Test invalid input, edge cases, and unauthorized input.
- Reject unsafe or unexpected request parameters.

## Security, privacy, and ownership requirements

- never show hashed passwords.
- never show demo credentials in production.
- do not create login bypasses that work in production.
- keep all private data behind authentication and policies.
- do not trust frontend IDs or hidden fields.
- avoid leaking details in errors, logs, notifications, or progress screens.

## Restricted hosting requirements

- Do not require cron.
- Do not require queue workers.
- Do not require artisan commands for normal usage.
- Do not require terminal access.
- If this feature would normally run in the background, implement a web-triggered Livewire flow.
- Use small chunks for heavy operations.
- Add progress, retry, resume, and failure reporting where long processing exists.
- Add protected maintenance-center integration when this step creates maintenance-type work.
- Document exact-time automation limitations honestly.
- Never say that a scheduler, worker, or command will handle something unless there is a web-only fallback.

## Factory and seeder requirements

- Add or update factories when this feature creates or depends on a model.
- Add named factory states for normal, edge, empty, heavy, private, shared, archived, deleted, failed, or demo states where relevant.
- Add or update seeders so the feature can be tested immediately on `https://ruflo.test/`.
- Make demo data realistic enough to show the UI state.
- Ensure at least two users exist for privacy tests when private data is involved.
- Ensure seeded data does not accidentally create global access.
- Ensure demo credentials remain local/testing/demo only.
- Document any seed data added.
- Add tests or smoke checks proving factories/seeders work where practical.
- Do not seed production-only unsafe data.

## Tests/checks to add or run

- test demo panel appears locally.
- test demo panel is hidden in production mode.
- test seeded demo users can login.
- test guest redirects from private pages.
- add or update feature tests for the touched flow.
- add at least one multi-user privacy test when private data is involved.
- record test command/check results in TODO_MASTER_TEST_REPORT.md.

## Documentation updates

- Update the main documentation for this exact step.
- Update changelog with this exact step name.
- Update `TODO_MASTER_PROGRESS.md` with this exact step status.
- Update `TODO_MASTER_CHECKLIST.md` with this exact step task list if needed.
- Update `TODO_MASTER_DECISIONS.md` for architectural choices.
- Update `TODO_MASTER_RISKS.md` for blockers, limitations, or unsafe tradeoffs.
- Update `TODO_MASTER_TEST_REPORT.md` with tests/checks run.
- Do not collapse documentation into a generic “steps remaining” sentence.
- Document restricted-hosting behavior if this step touches processing, reminders, recurrence, imports, exports, cleanup, files, or maintenance.
- Document free-only limitations if this step could normally use paid services.

## Acceptance criteria

- Step 010 is individually completed and not grouped with other steps.
- the repository progress file marks this exact step only when this exact step is done.
- no progress line says 'grouped future step range' or any other compressed range.
- the feature follows Laravel 13 + Livewire + Flux + Tailwind CSS 4 rules.
- no Volt code is introduced; touched Volt code is migrated.
- the feature works in restricted hosting mode without cron, workers, terminal, or artisan dependency for normal usage.
- all visible text is translated through current English language files.
- backend authorization and ownership checks protect all private data.
- factories/seeders are updated when this feature needs demo/test data.
- tests/checks are run or blockers are documented honestly.
- documentation, changelog, risk file, test report, and progress files are updated.
- a meaningful commit is prepared after the step is stable.

## Final response for this step

When this step is complete, the coding agent must report:

- exact step number and name
- what was implemented
- what was inspected
- what files/areas changed
- what validation/request/rule work was done
- what Livewire/Flux UI was done
- what security/privacy protections were added
- what restricted-hosting fallback exists
- what factories/seeders were added or updated
- what translations were added or updated
- what tests/checks/builds were run
- what documentation and progress files were updated
- what risks remain
- commit message used or prepared
- next exact step number
