# Feature Catalog — 100 Free Todo/Productivity Steps

## 001. Project audit and foundation

Analyze the project, Laravel version, package stack, auth, frontend, routes, views, tests, docs, and current architecture. Prepare the professional Laravel 13 + Livewire + Flux foundation without building all features yet.

## 002. Upgrade and normalize Laravel 13 stack

Verify or upgrade safely to Laravel 13 conventions. Remove outdated patterns, document blockers, keep behavior stable, and ensure the app follows modern Laravel structure.

## 003. Livewire and Flux foundation

Install/verify latest Livewire and Flux v2, standardize UI components, create base layout patterns, and prepare interactive screens with class-based Livewire only.

## 004. Remove Volt and migrate to normal Livewire

Find every Volt usage, migrate to normal class-based Livewire components, preserve behavior, remove unused Volt dependency/config safely, update tests and docs.

## 005. Tailwind CSS 4 and SCSS design layer

Standardize Tailwind CSS 4 usage and add SCSS only as a clean supporting layer for tokens, reusable helpers, print styles, and accessibility utilities.

## 006. Restricted hosting web-only mode

Remove assumptions about terminal, cron, workers, artisan, supervisor, and shell scripts. Prepare web-only processing patterns and maintenance UI principles.

## 007. Web installer and updater

Create protected web installer/updater principles for restricted hosting: environment checks, safe setup status, database readiness, migrations strategy, and no public setup risk.

## 008. Protected maintenance center

Build protected web maintenance center for owner/admin: health checks, cleanup, processing status, retry/resume tools, demo seed generation, and safe cache/storage controls.

## 009. Domain and ruflo.test readiness

Ensure the app works at https://ruflo.test/ with correct generated URLs, redirects, invite links, export links, notifications, and protected downloads.

## 010. Authentication and login UX

Review auth flow, login page, local/demo credential panel, safe environment protection, quick demo login usability, and no production password exposure.

## 011. Complete factories for all models

Create or update factories for every model, all states, edge cases, private/shared data, lifecycle states, and realistic demo content.

## 012. Complete seeders for all models

Create full seeders for every model and scenario so app is usable immediately after setup: demo users, tasks, projects, tags, reminders, recurrence, comments, attachments, settings, activity, invites.

## 013. Demo users and login panel

Show local/testing/demo-only seeded users on login page with username/email and fixed demo passwords. Never show in production.

## 014. Dedicated request classes

Create dedicated request validation classes for request-based actions. Keep validation out of controllers and duplicated Livewire code where appropriate.

## 015. Reusable custom validation rules

Create dedicated reusable rules for ownership, lifecycle, invite token, recurrence, reminder time, file upload, import/export, settings, roles, and other business validation.

## 016. English localization and message cleanup

Translate all visible texts and errors into English using current language files. Remove hardcoded UI, validation, success, error, activity, and notification messages.

## 017. Private workspace model

Implement private-by-default workspace behavior. Scope all personal data to the authenticated user or allowed workspace.

## 018. Ownership and query scoping

Add strict ownership scopes for tasks, projects, tags, reminders, notifications, activity, comments, attachments, exports, settings, and dashboard widgets.

## 019. Authorization policies

Create/normalize policies for every resource and action. Backend authorization is mandatory everywhere.

## 020. Guest and route protection

Protect private routes from guests. Separate public pages from private app pages. Ensure no temporary public private routes remain.

## 021. Core task creation

Implement task creation with authorization, validation, translations, Livewire UI, Flux forms, ownership assignment, tests, and seed states.

## 022. Task list and private task views

Implement private task list and task detail behavior with scoped queries, pagination, empty states, loading states, and no data leakage.

## 023. Task editing

Implement safe task editing with request classes/rules, Livewire forms, validation, authorization, and activity hooks.

## 024. Task completion and reopening

Implement complete/reopen lifecycle actions. Completion is not deletion. Reopen is intentional. Add tests and UI feedback.

## 025. Task archive and restore

Implement archive/restore lifecycle actions with clear UI, confirmation where needed, scoped queries, tests, and documentation.

## 026. Task deletion and trash behavior

Implement safe delete/trash behavior, restore/permanent delete rules if supported, confirmations, policies, and tests.

## 027. Task lifecycle state machine

Define allowed state transitions, reject invalid transitions, document behavior, and test edge cases.

## 028. Projects and lists

Add project/list organization, ownership, lifecycle, UI, filters, seed data, policies, tests, and docs.

## 029. Tags and labels

Add scoped tags/labels, assignment rules, filters, UI, validation, duplicate handling, tests, and docs.

## 030. Priorities

Add priority logic with fixed translated levels, badges, filtering, sorting, validation, seeds, and tests.

## 031. Due dates and date logic

Add due date logic, no due date state, due today, due soon, overdue, timezone principles, UI badges, tests, and docs.

## 032. Today view

Add today view using documented logic, private scope, recurring/reminder integration readiness, empty states, tests.

## 033. Overdue view

Add overdue view, excluding completed/archived/deleted/skipped items according to rules, with tests and docs.

## 034. Upcoming view

Add upcoming view with configurable safe range, sorted output, recurring readiness, private scope, and tests.

## 035. Search

Add private task/project/tag search with validation, debouncing, Livewire UX, pagination, no leakage, and tests.

## 036. Filters

Add status/project/tag/priority/due date/completed/archived/recurring/reminder filters with safe validation and private scope.

## 037. Sorting

Add safe sorting options with validation, default ordering, no injection risk, and tests.

## 038. Saved views

Add user-saved filters/views such as Today, Next 7 Days, High Priority, Waiting, No Due Date, and custom saved views.

## 039. Bulk selection and actions

Add bulk selection, complete/reopen/archive/restore/delete/move/tag/priority/due date actions with item-level authorization and tests.

## 040. Kanban board

Add free Livewire kanban board using statuses/projects/priorities, Flux cards, drag/drop if safe, permissions, and fallback buttons.

## 041. Calendar view

Add free calendar-style views for due dates, reminders, and recurring occurrences using self-hosted/browser-friendly logic, no paid calendar API.

## 042. Subtasks and checklists

Add subtasks/checklists inside tasks with ordering, completion, nested limits, progress, permissions, and tests.

## 043. Task templates

Add reusable task/project templates, checklist templates, recurring templates, and quick-create from templates.

## 044. Quick capture inbox

Add inbox for fast task capture, later sorting into projects/tags/due dates, with Livewire quick entry UX.

## 045. Focus mode

Add focus mode showing 1-3 important tasks, distraction-light UI, quick complete/snooze/defer, and dashboard integration.

## 046. Goals and milestones

Add goals/milestones linked to tasks/projects with progress, due dates, priorities, and dashboard widgets.

## 047. Habits tracker

Add free habit tracking with daily/weekly habits, streaks, check-ins, recurrence integration, and no paid services.

## 048. Pomodoro/focus timer

Add browser-based Pomodoro/focus timer tied to tasks, with local/session state and optional stored time logs.

## 049. Time tracking

Add manual and timer-based time tracking per task/project, reports, edits, permissions, and no external services.

## 050. Waiting/blocker/dependency system

Add blocked/waiting states, task dependencies, blockers, dependency validation, and smart views.

## 051. Smart views and cleanup views

Add smart views for stale tasks, no due date, overdue high priority, recently changed, abandoned projects, and cleanup suggestions.

## 052. Automation rules web-only

Add simple local automation rules triggered by user actions/web visits, no cron/workers: when completed, when due today, when priority high, etc.

## 053. Manual web processing engine

Create generic Livewire chunk processing engine for imports, exports, reminders, recurrence, cleanup, and maintenance tasks.

## 054. Reminder system web-mode

Implement reminders processed on app visit/manual trigger, with in-app notifications, skipped states, preferences, and tests.

## 055. Notification center

Add in-app notification center with unread/read, safe links, preferences, privacy scope, and no email dependency.

## 056. Daily summary dashboard

Add dashboard daily summary instead of cron email: today, overdue, upcoming, high priority, reminders, recurring, habits.

## 057. Recurring task rules

Add recurrence rules: daily, weekly, monthly, custom intervals, safe generation window, and validation.

## 058. Recurring occurrence generation

Add idempotent on-demand occurrence generation, duplicate prevention, limited future window, web processing, and tests.

## 059. Recurring exceptions

Add skipped, edited, moved, archived, completed recurring occurrence exceptions with safe UI and docs.

## 060. Recurring edit occurrence vs series

Implement clear UI and backend rules for editing one occurrence, future occurrences if supported, or entire series.

## 061. Dashboard foundation

Build dashboard cards/widgets with today, overdue, upcoming, priority, reminders, recurring, goals, habits, projects, time tracking.

## 062. Dashboard customization

Allow user to show/hide/reorder widgets, choose compact/detailed mode, include/exclude shared tasks, and persist preferences.

## 063. Project progress dashboard

Add project/list progress, overdue per project, completion ratio, and cleanup hints with optimized scoped queries.

## 064. Reports overview

Add free local reports: completed tasks, time tracked, habits streaks, project progress, overdue trends, no paid analytics.

## 065. Charts without paid services

Add simple self-hosted/browser charts if needed using free libraries or CSS/SVG, accessible text summaries, and no external paid analytics.

## 066. Activity history

Track meaningful changes: task lifecycle, project/tag/reminder/recurrence/comment/attachment/import/export/settings/collaboration events.

## 067. Task timeline UI

Add task timeline with Flux components, pagination/load more, safe deleted-object handling, translations, and tests.

## 068. Collaboration foundation

Add shared projects/lists, roles, permissions, members, private-by-default rules, and role-based UI.

## 069. Link-only invite system

Implement copyable link-only invites, no emails, expiration/cancellation/single-use if chosen, role/scope validation, tests.

## 070. Member management

Add member list, role badges, role changes, remove member, access revocation, activity, and tests.

## 071. Shared dashboard/search/filter scope

Ensure shared tasks/projects appear only where allowed in dashboard/search/filters/activity/notifications.

## 072. Comments

Add task comments with permissions, editing/deletion, activity, notifications, pagination, XSS safety, and tests.

## 073. Mentions

Add mention suggestions only for allowed users, mention notifications, no leakage, and no access grant by mention unless explicitly designed.

## 074. Comment moderation

Add owner/manager moderation rules, deleted/hidden states, activity, and safe UI.

## 075. Attachments

Add private task/comment attachments with upload validation, private storage, protected downloads/previews, and role permissions.

## 076. Image handling

Add safe image validation/optimization/thumbnails only if safe on shared hosting, with web processing fallback and no public leakage.

## 077. Storage and cleanup center

Add storage usage page, orphan cleanup, expired export cleanup, temp file cleanup, and no terminal requirement.

## 078. Import wizard

Add Livewire import wizard: upload, validate, preview, duplicate detection, confirm, chunked processing, progress, retry/resume.

## 079. Export wizard

Add Livewire export wizard: scope, format, include/exclude options, private file generation, progress, protected download, expiration.

## 080. CSV and JSON portability

Support CSV/JSON export/import, versioned JSON format, CSV formula injection protection, and ownership-safe import.

## 081. Backup and restore principles

Add backup/export behavior and restore preview if safe. Do not pretend full restore exists unless actually implemented and tested.

## 082. Settings foundation

Add user preferences and workspace settings with clear personal/shared separation and authorization.

## 083. Language and timezone settings

Add language/timezone/date/time preferences affecting reminders, recurrence, dashboard, activity, imports/exports where supported.

## 084. Notification preferences

Add granular preferences for reminders, summaries, comments, mentions, collaboration, attachments, imports/exports.

## 085. Privacy settings

Add privacy controls for shared dashboard data, activity visibility, email/detail visibility if any, export behavior, and collaboration boundaries.

## 086. Admin/security center

Add protected web admin/security center: users, workspaces, roles, invites, failed processes, storage, health, logs summaries, no sensitive leaks.

## 087. Demo reset and sample data tools

Add protected local/testing/demo-only reset/regenerate demo data tools through web UI, never production-exposed.

## 088. PWA basics free

Add optional free PWA basics: manifest, icons, offline fallback page, installability, cache strategy carefully avoiding private data leakage.

## 089. Offline-friendly UX

Add safe browser-side UX for temporary draft capture if offline, without promising full sync unless implemented and tested.

## 090. Keyboard shortcuts

Add optional keyboard shortcuts for quick create, search, focus mode, complete task, with accessible help and no interference.

## 091. Command palette

Add free Livewire/Flux command palette for navigation, quick actions, search, and shortcuts.

## 092. Help center and onboarding

Add local help pages, guided onboarding, empty-state guidance, demo walkthrough, and no external paid tools.

## 093. Tooltips and microcopy polish

Add consistent helper text, tooltips, confirmations, error copy, empty states, and friendly UX without overdoing humor in critical places.

## 094. Accessibility full pass

Review headings, labels, focus, keyboard, contrast, modals, dropdowns, screen reader text, and add tests/checklists where possible.

## 095. Responsive/mobile full pass

Review all screens on mobile/tablet/desktop, fix layout issues, touch targets, overflow, filter panels, and task cards.

## 096. Performance pass

Audit N+1 queries, pagination, dashboard query counts, Livewire re-rendering, search/filter performance, imports/exports, attachments.

## 097. Security pass

Audit CSRF, policies, roles, file uploads, private storage, imports, CSV injection, XSS, invite links, cache leaks, and debug leaks.

## 098. Testing expansion

Expand feature, Livewire, policy, privacy, upload, import/export, recurrence, collaboration, settings, and regression tests.

## 099. Documentation expansion

Complete docs for architecture, hosting mode, web maintenance, features, tests, deployment, limitations, and future work.

## 100. Release checklist and production readiness

Finalize release checklist, environment notes, web-only hosting notes, rollback notes, known risks, and production readiness report. Also review extra discovered feature ideas: Final 100-step QA and commit.

