# Factory Coverage

Step 011 covers the tracked application models:

- `App\Models\User`
- `App\Models\Project`
- `App\Models\Reminder`
- `App\Models\SavedTodoView`
- `App\Models\Tag`
- `App\Models\Todo`
- `App\Models\TodoChecklistItem`
- `App\Models\TodoTemplate`

`App\Models\Reminder` is currently a placeholder model with only an `id` and timestamps. Its default factory creates a valid record, but named reminder states are intentionally deferred until the reminder feature steps add owned reminder fields, lifecycle columns, and processing status.

## User Factory

`UserFactory` covers:

- default verified users with the shared test password,
- admin users for protected maintenance/admin surfaces,
- unverified users,
- custom passwords,
- confirmed two-factor authentication state,
- configured primary and secondary demo users.

Demo user factory states read from `config/demo.php` so the login panel, seeders, and tests stay aligned. The primary demo user is an admin; the secondary demo user is a normal user.

## Project And Tag Factories

`ProjectFactory` covers:

- default active projects,
- archived projects,
- explicit active state,
- named and color states,
- `work()` and `home()` demo states.

`TagFactory` covers:

- default user-owned tags,
- named and color states,
- `urgent()` and `waiting()` demo states.

## Reminder Factory

`ReminderFactory` covers valid placeholder record creation for the current reminder schema. It does not define active, due, sent, failed, retry, or processed states yet because the table has no columns for those concepts.

## Saved Todo View Factory

`SavedTodoViewFactory` covers:

- default user-owned saved task views with normalized empty criteria,
- `dueToday()` views for the active due-today bucket,
- `urgent()` views for urgent priority focus,
- `completed()` views for the completed lifecycle tab,
- explicit `criteria()` overrides for edge-case and stale-criteria tests.

Saved-view criteria stores only bounded URL state. Factory states do not create
shared/global visibility and remain owner-scoped through `user_id`.

## Todo Factory

`TodoFactory` covers:

- active, completed, archived, archived-completed, and soft-deleted Trash lifecycle states,
- low, normal, high, and urgent priority shortcuts,
- due today, overdue, upcoming, no due date, explicit due date, and max-length title states,
- project ownership helper through `forProject()`,
- tag ownership helpers through `forTag()` and `withTags()`.

The tag helpers avoid cross-user attachment by attaching only tags that share the todo owner.

## Todo Checklist Item Factory

`TodoChecklistItemFactory` covers:

- default contained checklist rows whose generated parent task shares the same
  owner,
- `forTodo()` for explicit parent task attachment,
- pending and completed states with matching `completed_at` behavior,
- explicit `position()` ordering,
- max-length title coverage through `longTitle()`.

Checklist rows are private resources and use the same `BelongsToUser` concern as
todos, projects, tags, and saved views.

## Todo Template Factory

`TodoTemplateFactory` covers:

- default user-owned private task templates,
- `task()`, `project()`, `checklist()`, and `routine()` template kinds,
- `private()` and `shared()` visibility states,
- due-offset defaults through `dueIn()`,
- 10-item checklist edge coverage through `heavyChecklist()`,
- max-length template names through `longName()`.

Shared template visibility is seed/test data for the later collaboration steps;
templates remain owner-scoped until member roles exist.

## Verification

`tests/Feature/FactoryCoverageTest.php` creates each tracked model through its factory and verifies the important current states and ownership boundaries.
