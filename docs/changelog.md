# Changelog

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
