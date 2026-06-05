# MASTER PROMPT — Laravel Todo Application Full Build

You are working inside an existing Laravel project.

Your task is to build a full production-style Todo application by following every rule and every step in this prompt pack.

You must not ask the user to run terminal commands manually. The target hosting is restricted shared hosting. The finished app must work through the browser.

## Critical global requirements

# GLOBAL MODERN STACK REQUIREMENT

Use the newest Laravel 13 conventions, latest Livewire, Flux v2, Tailwind CSS 4, and clean SCSS only where it gives real value.

Do not use Volt. If Volt exists, remove it safely and migrate all Volt logic to normal class-based Livewire components.

Use:
- Laravel 13 as backend foundation
- latest Livewire for dynamic interfaces
- Flux v2 as the main UI component system
- Tailwind CSS 4 as the main styling layer
- SCSS only for clean reusable custom styles, not messy page hacks
- Vite for frontend build
- Laravel policies for authorization
- Laravel validation/request classes/rules for safety
- Laravel factories/seeders/tests for quality
- Laravel localization for all visible text
- Laravel private storage for private files

Do not build old controller-heavy CRUD. Do not create custom messy UI when Flux provides a clean component. Do not hardcode visible text. Do not skip tests, docs, changelog, commits, security review, privacy review, or performance review.


# GLOBAL HOSTING LIMITATION REQUIREMENT

Build this project for restricted shared hosting.

Assume there is no:
- SSH
- terminal
- cron
- artisan access
- queue worker
- supervisor
- shell scripts
- long-running daemon
- root/server configuration access

Everything must work through authenticated web interface, preferably Livewire.

Rewrite every cron/job/artisan/scheduler/background-worker feature into web-triggered Livewire workflows.

Use:
- manual web triggers
- Livewire progress screens
- chunked processing
- retry/resume buttons
- timeout-safe batches
- protected maintenance/admin panel
- web health checks
- web cleanup tools
- web import/export wizards
- web recurring-task processing
- web reminder processing

Do not promise exact automatic background execution. If exact-time automation is impossible without cron/workers, document the limitation and provide a web-based fallback.


# GLOBAL DEMO, SEEDING, INVITES, REQUESTS, AND TRANSLATION REQUIREMENT

Create complete factories and seeders for every model and every important state.

The app must work immediately at:

https://ruflo.test/

All generated links must use the correct app URL configuration.

Invites must be link-only:
- no email invites
- no mail dependency
- invite link generated in UI
- copyable link
- safe expiration if supported
- cancellation support
- single-use behavior if chosen
- correct authorization and tests

On the login page show a local/testing/demo-only demo users panel with:
- display name
- username or email
- fixed demo password
- role/description
- quick login usability if safe

Never show demo credentials in production.

Every request-based action must have a dedicated request validation class where appropriate. Reusable business checks must use dedicated custom validation rules. All errors, labels, attributes, validation messages, success messages, confirmation text, and UI text must be translated at least in English using the current language files.

Do not hardcode visible text.


# LONG-RUN CODEX EXECUTION RULES

The agent must be able to run from one master prompt, but it must not assume one uninterrupted 20-200 hour session is reliable.

The agent must create a persistent progress system inside the repository.

Required progress files:
- TODO_MASTER_PROGRESS.md
- TODO_MASTER_CHECKLIST.md
- TODO_MASTER_DECISIONS.md
- TODO_MASTER_RISKS.md
- TODO_MASTER_TEST_REPORT.md
- TODO_MASTER_CHANGELOG.md

Before each step:
1. Read all progress files.
2. Check current git status.
3. Continue only from unfinished tasks.
4. Do not redo completed work unless broken.
5. Update progress before and after each major phase.

After each step:
1. Run available tests/build/checks where possible.
2. Update docs.
3. Update changelog.
4. Commit changes with meaningful message.
5. Write current status and next step into progress files.

If the agent is interrupted, the next run must continue from progress files.

Never make one giant risky commit. Commit after each stable step.
Never hide failures. Document blockers and continue with safe tasks when possible.


## Execution mode

Work through the steps in order:

- `01-project-foundation.md` — Full project analysis and professional Laravel Todo foundation preparation
- `02-private-workspace-authorization.md` — Private workspace, ownership, authorization, and user access logic
- `03-core-task-lifecycle.md` — Core Todo task lifecycle, safe actions, validation, and predictable behavior
- `04-organization-search-filters-bulk.md` — Task organization, projects/lists, tags, priorities, due dates, search, filters, sorting, and bulk actions
- `05-reminders-notifications-web-mode.md` — Reminders, notifications, overdue alerts, daily summary, and web-triggered scheduling
- `06-recurring-tasks-web-mode.md` — Recurring tasks, repeat rules, generated occurrences, exceptions, and safe recurrence lifecycle
- `07-dashboard-productivity.md` — Productivity dashboard, widgets, statistics, progress, and optimized private overview
- `08-activity-history-audit.md` — Activity history, audit trail, timeline, privacy-safe logs, and change tracking
- `09-collaboration-invites-links.md` — Collaboration, shared projects, members, roles, permissions, and link-only invites
- `10-comments-mentions.md` — Task comments, discussion, mentions, comment permissions, notifications, and moderation
- `11-attachments-private-storage.md` — Attachments, uploads, private downloads, image validation, and storage security
- `12-import-export-backup.md` — Import, export, backup, data portability, duplicate handling, and privacy-safe data movement
- `13-settings-personalization.md` — User preferences, workspace settings, language, timezone, dashboard customization, and defaults
- `14-ui-ux-accessibility-polish.md` — UI/UX polish, accessibility, responsive design, states, and final design cleanup
- `15-final-qa-production-readiness.md` — Final testing, security review, performance review, CI/checks, release checklist, and production readiness

For every step:
1. Read global rules.
2. Read current progress files.
3. Inspect the project.
4. Implement only what belongs to the current step.
5. Use Laravel 13 + Livewire + Flux + Tailwind 4.
6. Do not use Volt.
7. Respect restricted hosting mode.
8. Add factories/seeders/tests/docs/changelog.
9. Commit stable work.
10. Update progress files.

## Step instructions

Read and execute every file in `steps/` in numeric order.

If a step is too large, break it into internal substeps, but keep the same step number in progress files.

## Required final result

The finished project must be:

- Laravel 13 style
- Livewire-first
- Flux UI based
- Tailwind CSS 4 based
- no Volt
- restricted-hosting compatible
- web-interface-only for maintenance/processing
- no cron dependency
- no worker dependency
- no artisan dependency for normal usage
- fully seeded
- demo-ready at https://ruflo.test/
- link-only invites
- login page demo users panel in local/testing/demo only
- dedicated request validation classes/rules where appropriate
- translated English validation and UI messages
- private-by-default
- secure
- tested
- documented
- production-readiness reviewed
