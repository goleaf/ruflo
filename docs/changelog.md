# Changelog

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
