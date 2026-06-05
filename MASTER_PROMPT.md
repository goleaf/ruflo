# MASTER PROMPT — Laravel Todo Application 100 Ultra-Detailed Steps

You are working inside an existing Laravel project.

Read this file, `GLOBAL_RULES.md`, `STEPS_INDEX.md`, and every file in `steps/` in numeric order.

You must execute the plan step by step.

Do not ask the user for confirmation between steps.

Do not compress progress. Never write `grouped future step range`. Every step must be tracked individually.

If progress files do not exist, create them from `progress-templates/`.

The final app must use Laravel 13, latest Livewire, Flux v2, Tailwind CSS 4, clean SCSS where useful, no Volt, free-only features, restricted hosting web-only processing, complete seeds, link-only invites, local/testing demo users on login page, dedicated request classes, custom validation rules, and English translations.

## Required execution loop for every single step

1. Read `GLOBAL_RULES.md`.
2. Read the exact `steps/step-XXX-*.md` file.
3. Read all progress files.
4. Inspect current project state.
5. Implement only that exact step.
6. Add/update tests.
7. Add/update factories/seeders if relevant.
8. Add/update translations.
9. Add/update docs/changelog/progress/risk/test report.
10. Run available tests/checks/builds where possible.
11. Review git diff.
12. Commit stable changes with a meaningful message.
13. Mark only that exact step as completed.
14. Continue to the next exact step.

## Forbidden progress style

Do not write:

- `[ ] grouped future step range`
- `remaining steps`
- `all future steps`
- `steps not started yet`

Instead, every step from 001 to 100 must have its own line.

## Global rules

# GLOBAL RULES — Laravel Todo 100-Step Ultra Detailed Master Plan

## Non-negotiable stack

Use Laravel 13 conventions, latest Livewire, Flux v2, Tailwind CSS 4, and clean SCSS only where useful.

Do not use Volt. If Volt exists, remove it safely and migrate every Volt component to normal class-based Livewire components.

Use Flux as the main UI system. Do not create custom messy UI when Flux already provides a clean component.

Use Livewire for dynamic browser behavior. Do not build old controller-heavy CRUD for interactive screens.

Use Laravel policies, dedicated request classes, custom validation rules, factories, seeders, tests, localization, private storage, and modern architecture.

## Free-only rule

Use only free, open-source, first-party, self-hosted, browser-native, or local features.

Do not require paid APIs, paid SaaS, paid background workers, paid analytics, paid AI, paid email, paid OCR, paid maps, paid search, paid file conversion, or paid automation platforms.

If a feature normally needs paid infrastructure, create a free local/web-only version or document it as optional and disabled.

## Restricted hosting rule

Assume no SSH, no terminal, no cron, no queue worker, no supervisor, no artisan access, no shell scripts, no root access.

Everything must work through authenticated web UI.

Long operations must be Livewire-driven, chunked, timeout-safe, resumable, retryable, visible, and documented.

No critical production behavior may depend on terminal commands.

## Domain, demo, seeds

The app must work at https://ruflo.test/

Create factories and seeders for every model and every important state.

Login page must show local/testing/demo-only demo users with username/email and fixed demo password. Never show demo credentials in production.

## Invites

Invites must be link-only. No email invite dependency.

The UI must generate copyable invite links. Links must support cancellation, expiration if supported, and safe role/scope behavior.

## Validation and translations

Every request-based action must have a dedicated request class where appropriate.

Repeated business validation must use reusable custom validation rules.

All visible text and all validation/action messages must be translated at least in English through current language files.

No hardcoded visible text.

## Progress file rule

The agent must maintain these files in repository root:

- TODO_MASTER_PROGRESS.md
- TODO_MASTER_CHECKLIST.md
- TODO_MASTER_DECISIONS.md
- TODO_MASTER_RISKS.md
- TODO_MASTER_TEST_REPORT.md
- TODO_MASTER_CHANGELOG.md

Every step must be listed separately.

Never write a compressed progress line such as:

- grouped future step range

That is forbidden.

Each step must have its own checkbox, status, notes, risks, tests, and commit reference when possible.

