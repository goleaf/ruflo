# Changelog

## 2026-06-05 - Step 007 Web installer and updater

### Implemented

- Added a protected setup status page at `/settings/setup` behind `auth`, `verified`, and `password.confirm`.
- Added `InspectSetupStatus` and `SetupStatusReport` to inspect app key, HTTPS URL, database, migrations table, pending migrations, sync queue mode, restricted-hosting mode, and storage writability without running shell commands.
- Added English setup translations in `lang/en/setup.php`.
- Added setup navigation inside the settings layout.

### Testing

- Added setup status tests for route protection, password confirmation, rendering, and the status inspector.

### Documentation

- Added `docs/web-installer-updater.md` documenting the status-only web updater foundation and the no-public-installer policy.

## 2026-06-05 - Step 006 Restricted hosting web-only mode

### Implemented

- Added `config/hosting.php` and `App\Data\Hosting\WebProcessingProfile` to make chunked, resumable, web-triggered processing defaults explicit.
- Changed the default queue connection to `sync` so normal app usage does not imply a queue worker.
- Simplified `composer run dev` to Vite only because Laravel Herd serves the application locally.
- Removed the default console `inspire` command so there are no application console workflows.
- Updated `.env.example` for `https://ruflo.test`, sync queue defaults, and restricted-hosting processing knobs.

### Testing

- Added restricted-hosting tests that guard config defaults, queue/dev script assumptions, the empty console route, and the absence of `app/Jobs`.

### Documentation

- Added `docs/restricted-hosting.md` with the web-only processing contract and limitations.

## 2026-06-05 - Step 005 Tailwind CSS 4 and SCSS design layer

### Implemented

- Kept `resources/css/app.css` as the Tailwind CSS 4 and Flux UI entry using CSS-first `@theme` configuration.
- Added `resources/scss/app.scss` as a separate Vite entry for reusable tokens, accessibility helpers, shared surfaces, and print rules.
- Moved the welcome-page grid and veil styles out of inline Blade CSS and into the SCSS surface layer.
- Added the free `sass-embedded` compiler for Vite SCSS support.

### Testing

- Added `FrontendAssetPipelineTest` to guard the Tailwind/SCSS/Vite contract.

### Documentation

- Added `docs/frontend-design-system.md` with Tailwind 4, Flux, and SCSS usage rules.

## 2026-06-05 - Step 4 stabilization

### Implemented

- Added project rename support in the management modal with owner-scoped lookup,
  authorization, validation attributes, and tests.
- Added bulk restore and bulk move actions. Both re-scope selected IDs to the
  current user; bulk move validates the target project belongs to the user and
  is active.
- Added due-date "with/without" filters, project-name sorting, safer validation
  for selected IDs, and empty-state copy driven by the active filter context.

### Testing

- Full suite: 122 passed.

## 2026-06-05 - Step 4 Task organization, filters, search, sorting, bulk actions

### Implemented

- Added **projects** (owner-scoped task grouping): `projects` table, `todos.project_id` (nullable, `nullOnDelete` so deleting a project keeps its tasks as "No project"), `ProjectPolicy`, and create/rename/archive/restore/delete actions. Projects archive (reversible) distinctly from delete.
- Added **tags**: `tags` table + `tag_todo` pivot, per-user unique normalized (squished, lower-cased) names via `firstOrCreate`, `TagPolicy`, create/delete actions. Deleting a tag keeps the tasks.
- Added **priority** (`App\Enums\Priority`: low/normal/high/urgent with labels, colors, sort weights) and **due dates** on tasks, with active-only date buckets (today/overdue/upcoming) as model scopes + `isOverdue()`/`isDueToday()` helpers. Completed/archived tasks are never overdue.
- Centralized **search/filter/sort** in `TodoListQuery::filtered()` with a sanitized `TodoFilters` value object: title search with escaped LIKE wildcards (`ESCAPE` clause), filters by lifecycle/project/"none"/tag/priority/due, allow-listed sorting (created/due/priority/title, asc/desc) safe against `?sort=` injection, and 15/page pagination.
- Added **bulk actions** (complete/archive/delete) that re-scope the selected ids to the user's own tasks inside the query, so foreign ids are silently excluded and bulk complete/archive respect lifecycle state.
- Kept `project_id` out of `#[Fillable]`; `CreateTodo`/`UpdateTodo` set it directly only after re-scoping project and tag ids to the owner (`ResolvesTodoOrganization`).

### UI

- Rebuilt the task workspace: summary stats (active/overdue/completed/archived), filter toolbar, bulk toolbar, per-row priority/due/project/tag badges, pagination, an edit modal with all fields, and a "Manage" modal for projects and tags. Added reusable `x-ui.stat`. All copy in `lang/en/todos.php`; pickers only list the user's own resources.

### Testing

- Added `ProjectTest` (7), `TagTest` (6), `TodoOrganizationTest` (18): creation with organization data, ownership-safe project/tag assignment (foreign refs dropped), per-field validation, project/tag/priority/search filtering with cross-user isolation, LIKE-wildcard literal handling, due-date buckets + overdue summary, priority sorting, sort-injection fallback, and bulk actions that can't touch another user's tasks.
- Enriched the seeder: two users each with projects (incl. archived), tags, and tasks across all states (today/overdue/upcoming/high-priority/completed/archived).
- Full suite: 113 passed (was 82).

### Documentation

- Added `docs/task-organization.md` covering projects, tags, priority, due-date buckets, the timezone assumption, search/filter/sort safety, bulk-action scoping, performance/indexes, and what Step 5 builds next.

### Intentionally not implemented

- Manual ordering, saved filter views, sub-projects, project detail pages, reminders, recurring tasks, dashboard, collaboration.

## 2026-06-05 - Step 3 Core task lifecycle

### Implemented

- Defined an explicit task state machine: active ⇄ completed, active/completed → archived → (restore to prior bucket), and any non-deleted → trashed (soft delete). States are derived from `is_completed`, `archived_at`, and `deleted_at`; archived takes precedence over completion.
- Added `archived_at` to `todos` with a `(user_id, archived_at)` index; archive is distinct from both completion and deletion.
- Added `App\Enums\TodoStatus` (Active/Completed/Archived) with translatable labels and badge colors, and model helpers/scopes (`status()`, `isActive()`, `isArchived()`, `scopeActive/Completed/Archived`).
- Added one action per transition: `UpdateTodo`, `ArchiveTodo`, `UnarchiveTodo`; hardened `ToggleTodoCompletion` and `ClearCompletedTodos` to respect archive state. `archived_at` is set directly (system-controlled, never mass-assignable).
- Added `InvalidTodoTransition` so completing or editing an archived task fails safely as a translatable warning, never a 500 or a leak.
- Extended `TodoListQuery` with `forStatus()` buckets and a three-way `summaryFor()` (active/completed/archived counts) in a single scoped query.
- Added domain events `TodoUpdated`, `TodoArchived`, `TodoUnarchived` (alongside the existing create/toggle/delete/clear events) for future activity history and reminders.

### UI

- Rebuilt the task list around a lifecycle segmented control (Flux Free has no tabs component) with live per-bucket counts, a create form on the Active tab, state-aware row actions in a dropdown (edit/archive on non-archived, restore on archived, delete always with `wire:confirm`), an edit modal, a reusable `x-ui.status-badge`, and per-tab empty states.
- All new copy added to `lang/en/todos.php`; nothing user-facing is hardcoded.

### Testing

- Added `TodoLifecycleTest` (18 tests): status derivation, per-bucket listing and summary, archive/restore (completion preserved), archived-completion rejection, edit + edit validation, archived-edit refusal, soft delete, clear-completed isolation from archive, invalid-tab fallback, and cross-user denial across every lifecycle action (data-driven over toggle/edit/archive/restore/delete).
- Full suite: 82 passed (was 64).

### Documentation

- Added `docs/task-lifecycle.md`: states, allowed/rejected transitions, where each concern lives, events, validation, UI states, and what Step 4 builds next.

### Intentionally not implemented

- Projects/lists, tags, priorities, due dates, search, non-lifecycle filters, sorting, pagination, bulk actions, a trash-restore UI, reminders, recurring tasks, dashboard, and collaboration.

## 2026-06-05 - Step 2 Private workspace, ownership & authorization

### Inspected

- Confirmed Fortify web authentication (login, registration, verification, 2FA, passkeys); the authenticated user drives every scope.
- Confirmed private routes are grouped behind `['auth', 'verified']` in `routes/web.php`; no public route touches private data.
- Confirmed there are no roles, gates, teams, workspaces, or admin/multi-tenant logic — the owning `User` is the workspace boundary.
- Confirmed `TodoPolicy` resolves for the `Todo` model and Step 1 cross-user isolation tests still pass.

### Prepared

- Added `App\Models\Concerns\BelongsToUser` as the single source of truth for ownership: `scopeOwnedBy()`, `isOwnedBy()`, and the `user()` relationship. Future todo resources reuse it for identical behavior.
- Bound the policy explicitly with `#[UsePolicy(TodoPolicy::class)]` on `Todo` instead of relying on naming-convention auto-discovery.
- Refactored `TodoListQuery` so every list, lookup, and counter flows through `ownedBy()`; client IDs resolve to not-found when foreign, so existence never leaks.
- Hardened mass assignment: `user_id` stays out of `#[Fillable]`; a test proves a submitted `user_id` is ignored.
- Seeded two isolated user workspaces so private isolation can be exercised by hand and by tests.

### Testing

- Added `TodoOwnershipTest`: policy resolution, owner-allow / non-owner-deny for view/update/complete/delete/restore, not-found (404) leakage behavior, `forceDelete` disabled, mass-assignment refusal, owner-scoped query + counters, and class-level abilities.
- Full suite: 62 passed (was 51).

### Documentation

- Added `docs/authorization.md` specifying the core invariant, workspace boundary, single-source-of-truth locations, query scoping, route protection, no-leak error behavior, preparation for dashboard/search/filters/bulk/activity/notifications/collaboration, and testing requirements.

### Intentionally not implemented

- Task lifecycle screens, edit/archive/restore actions, dashboard, search, filters, bulk actions, reminders, collaboration, roles, and any `Workspace` model (the `User` remains the boundary for now).

## 2026-06-05 - Step 1 Todo foundation

### Analyzed

- Verified the app is a Laravel 13.14 Livewire starter-style project with Fortify authentication, Flux UI, Tailwind CSS v4, Vite, Pest, Pint, and SQLite.
- Confirmed the authenticated shell, settings routes, Fortify views, package scripts, test bootstrap, current database schema, existing reusable components, and current Todo implementation.
- Confirmed the full Pest suite passed before the Step 1 foundation edits.
- Confirmed this directory is a Git repository on `main`; Step 1 foundation changes are committed after verification.

### Prepared

- Added explicit Todo domain boundaries for actions, query logic, Livewire form validation, data transfer, authorization, and events.
- Added `TodoPolicy` with owner-only lifecycle checks and permanent deletion disabled by default.
- Added soft deletes to `todos` so delete behavior does not permanently remove user work.
- Added `TodoListQuery` for owner-scoped lists and aggregate counts.
- Added domain events for Todo creation, completion toggling, deletion, and clearing completed todos.
- Added reusable `x-ui.page-header` and `x-ui.empty-state` components for future Todo screens.
- Added `lang/en/todos.php` so Todo UI text and messages are translation-ready.

### Testing

- Enabled `RefreshDatabase` for Feature tests in the Pest bootstrap.
- Added Todo behavior tests for authentication, owner-scoped viewing, creation, validation, toggling, soft deletion, completed cleanup, and cross-user mutation attempts.
- Added Todo architecture tests to guard thin Livewire components, shared UI components, translation keys, and required documentation.

### Documentation

- Added `docs/todo-foundation.md` with analysis findings, architecture rules, ownership principles, validation rules, lifecycle rules, UI rules, translation rules, testing rules, run commands, and Step 2 direction.
- Added this changelog so future agents can see what Step 1 prepared and what remains intentionally unbuilt.

### Intentionally not implemented

- Projects, tags, priorities, due dates, reminders, dashboards, search, filters, bulk edit, activity history, notifications, collaboration, workspaces, roles, import, and export.
- Permanent deletion flows.
- Admin panel logic.
