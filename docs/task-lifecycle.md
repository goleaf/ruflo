# Task Lifecycle

This document describes how a task moves through its states, which transitions
are allowed, and where each rule is enforced. It builds directly on the
ownership and authorization model in [`authorization.md`](authorization.md):
every action below is owner-scoped and policy-checked.

## States

A task's state is derived from three columns; there is no stored "status"
field. {@see App\Models\Todo::status()} maps them to a display bucket.

| State | Condition | Where it shows |
| --- | --- | --- |
| **Active** | `archived_at` null, `is_completed` false | Active tab |
| **Completed** | `archived_at` null, `is_completed` true | Completed tab |
| **Archived** | `archived_at` set (any completion) | Archived tab |
| **Trashed** | `deleted_at` set (soft delete) | Nowhere (recoverable by design) |

`App\Enums\TodoStatus` is the enum for the first three (user-facing) buckets.
Archived takes precedence over completion: an archived task always reads as
"Archived" and keeps its underlying completion flag untouched so it can return
to the right bucket on restore.

## Allowed transitions

```
            complete
   Active  ─────────▶  Completed
      ▲                   │
      └──────────────────-┘
            reopen

   Active ─┐                        ┌─▶ Active      (was active)
           ├─ archive ─▶ Archived ──┤
 Completed ┘             (restore)  └─▶ Completed   (was completed)

   Active / Completed / Archived ──delete──▶ Trashed   (soft, recoverable)
```

Explicit rules:

- **Active ⇄ Completed** — `ToggleTodoCompletion`. Reversible; completion is
  never deletion.
- **Active/Completed → Archived** — `ArchiveTodo`. Sets `archived_at`; does not
  change completion; idempotent.
- **Archived → prior bucket** — `UnarchiveTodo` ("restore"). Clears
  `archived_at`; completion is preserved, so the task returns to Completed if
  it was completed before archiving, otherwise Active. Idempotent.
- **Any non-deleted → Trashed** — `DeleteTodo`. Soft delete. Recoverable at the
  data layer; `forceDelete` is disabled by policy, and a trash-restore UI is
  intentionally deferred.
- **Edit details** — `UpdateTodo`. Changes editable details such as title,
  priority, due date, project, and tags; trims the title at the action write
  boundary; re-scopes project and tag ids to the owner; never alters
  completion, archive, or deletion state.

## Rejected transitions (safe failures)

These are blocked in the action layer (`App\Exceptions\InvalidTodoTransition`)
and surfaced to the user as a calm, translatable warning toast — never a 500 or
a leak:

- **Complete/reopen an archived task** — must be restored first. The UI also
  hides the checkbox for archived rows; the backend rejects it regardless.
- **Edit an archived task** — must be restored first. The UI does not offer the
  edit action on archived rows; the backend rejects it regardless.

Idempotent no-ops (archiving an archived task, restoring a non-archived task)
return silently rather than erroring.

## Where each concern lives

| Concern | Location |
| --- | --- |
| State derivation & scopes | `App\Models\Todo` (`status()`, `isActive()`, `isArchived()`, `scopeActive/Completed/Archived`) |
| Mutations | `App\Actions\Todos\*` (one action per transition) |
| Invalid-transition guard | `App\Exceptions\InvalidTodoTransition` |
| Owner-scoped reads & buckets | `App\Queries\Todos\TodoListQuery` (`forStatus`, `summaryFor`) |
| Authorization | `App\Policies\TodoPolicy` (`complete`, `archive`, `restore`, `delete`, `update`, …) |
| UI state & feedback | `App\Livewire\Todos\Index` + `resources/views/livewire/todos/index.blade.php` |
| Status badge | `resources/views/components/ui/status-badge.blade.php` |

The Livewire component holds UI state only. It authorizes every call, resolves
every target through `TodoListQuery::findVisibleFor()` (so a foreign or unknown
ID is a not-found, never a leak), and delegates all writes to actions. No
business logic lives in the Blade view.

## Events (for future activity history & notifications)

Every transition dispatches a domain event so activity logging and reminders
can be added later without touching the actions:

`TodoCreated`, `TodoUpdated`, `TodoCompletionToggled`, `TodoArchived`,
`TodoUnarchived`, `TodoDeleted`, `CompletedTodosCleared`.

Notification/reminder rules to honor when those features arrive: completed and
archived tasks should not emit active reminders; deleted tasks emit none;
restoring or reopening may re-enable them per the rules documented in the
reminders step.

## Validation

- Title: `required|string|max:120`, normalized (trimmed) before persistence.
  `TodoForm` validates the Livewire input, `TodoData::fromArray()` trims the
  normal form path, and `CreateTodo` trims again at the write boundary so a
  manually constructed DTO cannot persist wrapper whitespace. Step 023 applies
  the same action-level trim to `UpdateTodo`.
- Tab/filter input is validated against `TodoStatus::tabValues()`; an unknown
  tab falls back to Active and never widens the query scope.

## UI states covered

Empty state per tab (active / completed / archived), inline validation errors,
success/warning toasts for every action, `wire:confirm` on destructive actions
(delete, clear completed), state-aware row actions (no complete/edit on archived
rows; restore only on archived rows), and a status badge per row. All
user-facing text is translatable via `lang/en/todos.php`.

## Intentionally not built yet

Projects/lists, tags, priorities, due dates, search, filters beyond the three
lifecycle tabs, sorting, pagination (the per-user list is currently small and
fully owner-scoped; pagination is the first thing to add when lists can grow),
bulk actions, a trash/restore-from-deleted UI, reminders, recurring tasks,
dashboard, and collaboration.

## What Step 4 should build next

Task organization: projects/lists, tags, priorities, due dates, and the
today/overdue/upcoming/search/filter/sort/bulk-action layer — all reusing the
ownership scope, the action/query/policy split, and the translation structure
established here.
