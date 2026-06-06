# Todo Foundation

## Purpose

This project is being prepared as a production-ready Laravel Todo application. Step 1 intentionally prepares boundaries, rules, tests, translations, and documentation before adding richer product features such as projects, tags, reminders, dashboards, collaboration, activity history, imports, or exports.

## Current Project Analysis

- Runtime detected by Laravel Boost: PHP CLI 8.4, Laravel 13.14.0, SQLite.
- Composer PHP requirement: `^8.3`.
- Main packages: Laravel Fortify 1.37, Livewire 4.3, Flux UI 2.14, Pest 4.7, Pint 1.29, Tailwind CSS 4.3, Vite 8.
- Authentication: Fortify-backed web authentication with login, registration, password reset, email verification routes, two-factor authentication, and passkeys enabled.
- Frontend stack: Blade, class-based Livewire full-page components, Flux UI free components, Alpine through Livewire, Tailwind CSS v4, Vite.
- Route organization: `routes/web.php` for public/authenticated app routes and `routes/settings.php` for authenticated settings routes.
- Middleware: authenticated app routes use `auth` and `verified`; settings use `auth`, `verified`, and password confirmation where needed.
- Current app shell: `resources/views/layouts/app/sidebar.blade.php` is the primary authenticated shell; `resources/views/layouts/app/header.blade.php` is also available.
- Reusable UI: starter kit components exist under `resources/views/components`; Step 1 adds shared `x-ui.page-header` and `x-ui.empty-state`.
- Testing: Pest is configured for Feature and Unit tests. Feature tests use `RefreshDatabase` globally in `tests/Pest.php`.
- Documentation: no previous project README, docs, changelog, or ADR folder was present. Step 1 adds this `docs/` area.
- Git: this checkout is a Git repository on `main`; Step 1 changes are committed as part of the foundation preparation workflow.

## Architecture Rules

Todo features must stay separated by responsibility:

- Livewire components own UI state, rendering, and calling application services only.
- Livewire components must authorize actions before mutating data.
- Livewire form objects own Livewire validation state and validation rules.
- Action classes under `app/Actions/Todos` own mutations.
- Query classes under `app/Queries/Todos` own reusable read queries, filtering, sorting, counts, and pagination.
- Data transfer classes under `app/Data/Todos` carry validated input into actions.
- Policies under `app/Policies` own authorization decisions.
- Events under `app/Events` describe important domain changes for future notification and activity-history listeners.
- Blade views must stay presentation-oriented and avoid direct database queries or business logic.

Do not place Todo business logic directly in route closures, Blade views, random helpers, or large Livewire methods.

## Ownership And Authorization

Todo data is private by default.

Every Todo-related model must have a clear owner or workspace boundary before it is exposed through routes, queries, or actions. Current single-user ownership is represented by `todos.user_id`. Future workspace support must introduce explicit workspace membership and role checks before shared data becomes visible.

Required rules:

- Every query must be scoped to the authenticated user or authorized workspace.
- Every mutation must authorize through a policy or gate before calling an action.
- Client-provided IDs are untrusted. Resolve them through owner-scoped queries or authorize them before use.
- Denied private records should not leak existence. Use owner-scoped queries or not-found policy responses.
- Frontend hiding is not a permission system.
- Bulk actions must be owner-scoped and tested against another user's private records.

## Validation

Current Livewire Todo creation uses `App\Livewire\Forms\Todos\TodoForm`. Future HTTP endpoints should use Form Request classes; future Livewire forms should use Livewire Form objects.

Validation must cover create, update, completion, reopening, due dates,
filtering, sorting, bulk actions, archiving, unarchiving, deleting, restoring
deleted data, and collaboration actions when those features are introduced.
Validation should normalize input before it reaches action classes.

Step 021 hardens the core creation boundary: `CreateTodo` trims the task title
again before persistence, assigns ownership through `$user->todos()`, sets only
editable task details, re-scopes project and tag ids to the owner, and emits
`TodoCreated`. `Todo` mass assignment excludes ownership, project, completion,
archive, and deletion state so task creation cannot silently become a lifecycle
transition.

## Data Lifecycle

Deleting user work should be safe by default.

- Completion is not deletion.
- Archive is not deletion.
- Delete should soft-delete unless permanent deletion is deliberately designed and protected.
- Restore and force-delete abilities must be explicit in policies.
- Important state changes should emit events so future activity history and notification listeners can be added without rewriting actions.

Step 1 adds soft deletes to `todos` and keeps permanent deletion disabled in
`TodoPolicy::forceDelete`. Step 026 exposes a private Trash tab, restores
deleted tasks through `RestoreDeletedTodo`, and keeps force delete disabled and
absent from the UI.

## Query And Performance Rules

Future Todo lists must not load unbounded records.

- Use query objects for reusable list, count, search, filter, and dashboard logic.
- Use pagination or safe incremental loading before lists can grow.
- Avoid N+1 queries by eager loading required relationships.
- Keep dashboard counters in query classes and prefer grouped/aggregate queries over repeated ad hoc counts.
- Add indexes with migrations when introducing filters, sorts, or foreign keys.
- Never query from Blade templates.

`App\Queries\Todos\TodoListQuery` is the initial owner-scoped query boundary.

## UI Rules

The app uses the Laravel Livewire starter kit shell with Flux UI and Tailwind CSS v4.

Use existing Flux components before adding custom markup. Prefer shared Blade components for repeated structures. Step 1 introduces:

- `x-ui.page-header` for page title, description, and right-side page metadata/actions.
- `x-ui.empty-state` for consistent empty states.

Future Todo UI should reuse common patterns for page headers, cards, form fields, buttons, status badges, priority badges, empty states, filters, confirmation dialogs, pagination, and loading states. Do not introduce competing button styles, spacing systems, or card shapes without a clear design reason.

## Translation Rules

All user-facing Todo text must use translation keys.

Todo translations live in `lang/en/todos.php`. Add new labels, button text, placeholders, empty states, status names, priority names, validation attributes, toasts, and confirmation messages there before rendering them.

Do not hardcode Todo UI text in Blade or PHP classes except test fixture data.

## Testing Rules

Every Todo feature must include tests for:

- authentication redirects,
- ownership boundaries,
- authorization failures,
- validation failures,
- successful behavior,
- cross-user isolation,
- lifecycle behavior,
- query/filter behavior,
- dashboard counts,
- bulk actions.

The core invariant is: one user must never view, change, archive, unarchive,
delete, restore deleted data, or infer another user's private Todo data.

Step 1 adds tests for owner-scoped viewing, creation, validation, completion, soft deletion, clearing completed todos, and cross-user mutation attempts. It also adds architecture tests to keep Livewire thin and translation-based.

Step 021 adds `CoreTaskCreationTest`, which locks direct action creation,
event dispatch, bypassed-validation organization scoping, mass-assignment
guards, lifecycle-action completion, long-title validation, form-state
preservation, and create-form error placement.

Step 026 adds `TaskDeletionTrashTest`, which locks soft delete, restore from
Trash, event dispatch, idempotency, owner-scoped trash lookups, selected
deleted-task validation, bulk delete/restore behavior, dashboard trash counts,
and force-delete denial.

Step 027 adds `TaskLifecycleStateMachineTest`, which locks the accepted source
states, target buckets, idempotent no-ops, and direct-action invalid transition
guards for the centralized todo state machine, including language-backed
exception messages.

Step 028 adds `ProjectDetailTest`, which locks private project detail routing,
owner-scoped project/task rendering, archived project readability, translated
empty states, and project badge links from task list/detail surfaces.

## 2026-06-06 Recheck

The Step 001 recheck confirmed the foundation still matches the current master-plan rules after Steps 002-016:

- Laravel 13.14, Livewire 4.3, Flux 2.14, Fortify 1.37, Pest 4.7, Pint 1.29, and Tailwind CSS 4.3 are installed.
- The app URL resolves to `https://ruflo.test`, the local environment is `local`, and the normal queue connection is `sync`.
- No Volt usage was found in application, resource, route, config, package, or test files.
- The full route inventory includes public, dashboard, todos, settings, Fortify, Livewire, Flux, passkey, and health routes.
- Project/list creation, rename, archive, restore, deletion, no-project fallback, owner-only pickers, task movement, and cross-user isolation remain covered by feature tests.
- The current full suite passes after Step 016 with 170 tests and 620 assertions.

## How To Run

The project is served by Laravel Herd. Do not start a separate Laravel server for local browser use.

Common commands:

```bash
composer install
npm install
php artisan migrate --no-interaction
npm run build
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

Use `npm run dev` or `composer run dev` only when actively developing frontend assets. Neither command starts a queue worker for normal usage.

## Step 2 Historical Direction

Step 2 should focus on ownership, private workspace, authorization, and user access rules. Recommended next work:

- Formalize private workspace terminology and whether `User` remains the owner or a `Workspace` model is introduced.
- Add policy tests for all Todo lifecycle abilities.
- Decide if Todo creation remains available to every verified user or if workspace membership is required.
- Add route-level and action-level tests for ID guessing and private data leakage.
- Add pagination/filter foundations before expanding list features.

Do not start projects, tags, reminders, collaboration, or dashboard statistics until the ownership and workspace model is settled.
