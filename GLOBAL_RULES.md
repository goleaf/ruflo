# GLOBAL RULES — Laravel Todo 100-Step Master Plan

## Modern stack

Use the newest Laravel 13 conventions, latest Livewire, Flux v2, Tailwind CSS 4, and clean SCSS only where it gives real value.

Use Laravel 13 as backend foundation, latest Livewire for dynamic web UI, Flux v2 as the main UI component system, Tailwind CSS 4 as the main styling layer, Vite for frontend assets, Laravel policies for authorization, dedicated request validation classes, custom validation rules, factories, seeders, tests, localization, and private storage.

Do not use Volt. If Volt exists, remove it safely and migrate all Volt logic to normal class-based Livewire components.

Do not build old controller-heavy CRUD. Do not create custom messy UI when Flux provides a clean component. Do not hardcode visible text.

## Free-only requirement

Use only free, open-source, first-party, self-hosted, or locally available solutions.

Do not require paid APIs, paid SaaS services, paid cloud workers, paid queue infrastructure, paid analytics, paid AI services, paid maps, paid OCR, paid external search, paid file conversion, paid email delivery, or paid automation platforms.

If a feature normally uses paid infrastructure, implement a free local/web-only version or document it as optional and disabled.

## Restricted hosting

Build for shared hosting with no SSH, terminal, cron, artisan access, queue worker, supervisor, shell scripts, long-running daemon, root access, or server configuration access.

Everything must work through authenticated web UI.

All long operations must be chunked, web-triggered, timeout-safe, resumable, and visible through Livewire progress screens.

Do not require php artisan, cron, queue workers, or shell commands for normal production usage.

## Web-only processing

Replace background processing with Livewire action buttons, protected maintenance center, chunked processing, progress reports, retry buttons, resume buttons, cancel buttons where useful, on-demand reminder processing, on-demand recurring generation, web import/export wizards, web cleanup tools, and web health checks.

Do not promise exact-time automation without cron/workers. Document limitations honestly.

## Domain and demo readiness

The app must work immediately at https://ruflo.test/

All generated links must use configured app URL correctly.

Create complete factories and seeders for every model and every important state.

On login page, show a local/testing/demo-only demo users panel with display name, username/email, fixed demo password, role/description, and quick login usability if safe. Never show demo credentials in production.

## Link-only invites

Invites must be link-only. No email invite dependency.

The UI must generate copyable invite links. Invite links must support cancellation, expiration if supported, single-use behavior if chosen, role/scope validation, and safe access.

## Validation and localization

Every request-based action must have a dedicated request validation class where appropriate.

Repeated business validation must use custom validation rules.

Every visible text, validation message, error message, success message, confirmation message, field label, empty state, activity text, notification text, and setting label must be translated at least in English using current language files.

No hardcoded visible text.

## Long-run execution

The agent must maintain progress files in repository root:

- TODO_MASTER_PROGRESS.md
- TODO_MASTER_CHECKLIST.md
- TODO_MASTER_DECISIONS.md
- TODO_MASTER_RISKS.md
- TODO_MASTER_TEST_REPORT.md
- TODO_MASTER_CHANGELOG.md

Before each step, read progress files and current git status.

After each stable step, update docs, changelog, progress files, tests/checks, and commit.

If interrupted, continue from progress files.

Never make one giant risky commit.

## Quality standard

Every step must include project inspection, implementation, security review, privacy review, performance review, tests where possible, docs update, changelog update, progress update, git diff review, and meaningful commit.

Never claim something is done if tests/checks fail without documenting it.
