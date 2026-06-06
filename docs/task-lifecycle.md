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
| **Trashed** | `deleted_at` set (soft delete) | Trash tab |

`App\Enums\TodoStatus` is the enum for the user-facing buckets. Trash takes
precedence over archive and completion because soft-deleted tasks are not
actionable in the main workspace. Archived still takes precedence over
completion for non-deleted tasks: an archived task always reads as "Archived"
and keeps its underlying completion flag untouched so it can return to the
right bucket when unarchived.

## Allowed transitions

```
            complete
   Active  ─────────▶  Completed
      ▲                   │
      └──────────────────-┘
            reopen

   Active ─┐                        ┌─▶ Active      (was active)
           ├─ archive ─▶ Archived ──┤
 Completed ┘           (unarchive)  └─▶ Completed   (was completed)

   Active / Completed / Archived ──delete──▶ Trashed   (soft, recoverable)
      ▲              ▲              ▲           │
      └──────────────┴──────────────┴───────────┘
                         restore from trash
```

Explicit rules:

- **Active → Completed** — `CompleteTodo`. Completion is never deletion.
  Completing an already-completed task is a harmless no-op and does not emit a
  duplicate completion event.
- **Completed → Active** — `ReopenTodo`. Reopening preserves title, project,
  tags, priority, and due date. Reopening an already-active task is a harmless
  no-op and does not emit a duplicate reopen event.
- **Active/Completed → Archived** — `ArchiveTodo`. Sets `archived_at`; does not
  change completion; idempotent.
- **Archived → prior bucket** — `UnarchiveTodo`. Clears `archived_at`;
  completion is preserved, so the task returns to Completed if it was completed
  before archiving, otherwise Active. Idempotent. This is distinct from future
  soft-delete restore behavior.
- **Any non-deleted → Trashed** — `DeleteTodo`. Soft delete. Recoverable in the
  Trash tab. Idempotent direct action calls do not emit duplicate delete
  events.
- **Trashed → prior bucket** — `RestoreDeletedTodo`. Clears `deleted_at`;
  completion and archive state are preserved, so restored tasks return to
  Active, Completed, or Archived as they were before deletion. Idempotent.
- **Edit details** — `UpdateTodo`. Changes editable details such as title,
  priority, due date, project, and tags; trims the title at the action write
  boundary; re-scopes project and tag ids to the owner; never alters
  completion, archive, or deletion state.

## Rejected transitions (safe failures)

These are blocked in the action layer (`App\Exceptions\InvalidTodoTransition`)
and surfaced to the user as a calm, translatable warning toast — never a 500 or
a leak:

- **Complete/reopen an archived task** — must be unarchived first. The UI also
  hides the checkbox for archived rows; the backend rejects it regardless.
- **Edit an archived task** — must be unarchived first. The UI does not offer the
  edit action on archived rows; the backend rejects it regardless.
- **Edit/complete/archive/unarchive/delete a trashed task** — must be restored
  from Trash first. The UI offers restore only for trash rows; the backend
  resolves trash records through `findTrashedFor()` so visible-record actions
  cannot act on deleted IDs.
- **Permanent deletion** — `forceDelete` is disabled by policy and no permanent
  delete UI is exposed.

Idempotent no-ops (archiving an archived task, unarchiving a non-archived task)
return silently rather than erroring. Direct delete/restore action no-ops also
avoid duplicate activity events.

## Where each concern lives

| Concern | Location |
| --- | --- |
| State derivation & scopes | `App\Models\Todo` (`status()`, `isActive()`, `isArchived()`, `scopeActive/Completed/Archived`) |
| Transition map | `App\Enums\TodoTransition` + `App\Actions\Todos\TodoLifecycleStateMachine` |
| Mutations | `App\Actions\Todos\*` (one action per transition, guarded by the state machine) |
| Invalid-transition guard | `App\Exceptions\InvalidTodoTransition` |
| Owner-scoped reads & buckets | `App\Queries\Todos\TodoListQuery` (`forStatus`, `findVisibleFor`, `findTrashedFor`, `summaryFor`) |
| Authorization | `App\Policies\TodoPolicy` (`complete`, `reopen`, `archive`, `unarchive`, `delete`, `restore`, `update`, `forceDelete`, …) |
| UI state & feedback | `App\Livewire\Todos\Index` + `resources/views/livewire/todos/index.blade.php` |
| Status badge | `resources/views/components/ui/status-badge.blade.php` |

The Livewire component holds UI state only. It authorizes every call, resolves
every target through `TodoListQuery::findVisibleFor()` (so a foreign or unknown
ID is a not-found, never a leak), and delegates all writes to actions. No
business logic lives in the Blade view.

## Events (for future activity history & notifications)

Every state-changing transition dispatches a domain event so activity logging
and reminders can be added later without touching the actions:

`TodoCreated`, `TodoUpdated`, `TodoCompleted`, `TodoReopened`,
`TodoArchived`, `TodoUnarchived`, `TodoDeleted`,
`TodoRestoredFromTrash`, `CompletedTodosCleared`.

Step 024 replaces the former generic completion toggle with separate
completion and reopening actions/events. The row checkbox still gives the same
fast UX, but it calls the explicit transition for the task's current state and
uses state-specific translated accessibility labels.

Step 025 keeps archive reversal explicit as `UnarchiveTodo`,
`TodoUnarchived`, and `unarchiveTodo`/`bulkUnarchive` UI methods. This avoids
confusing "bring back from archive" with future soft-delete restore behavior.

Step 026 adds the Trash tab with `RestoreDeletedTodo`,
`TodoRestoredFromTrash`, and `restoreDeletedTodo`/`bulkRestoreDeleted` UI
methods. Bulk delete now delegates to `DeleteTodo` so delete events are not
skipped by a mass update. Permanent deletion remains disabled.

Step 027 centralizes accepted source states and target buckets in
`TodoLifecycleStateMachine`. The transition actions now call that state machine
before changing lifecycle columns, so direct action calls and Livewire calls
share the same valid-transition contract. Invalid transition exception messages
are stored under `lang/en/todos.php`.

Notification/reminder rules to honor when those features arrive: completed and
archived tasks should not emit active reminders; deleted tasks emit none;
unarchiving or reopening may re-enable them per the rules documented in the
reminders step. Restoring from Trash may also re-enable reminders according to
those future reminder rules.

## Contained checklist lifecycle

Step 042 adds checklist rows through `todo_checklist_items`. Checklist rows are
contained by a parent task and inherit the parent task's visibility and
mutability rules. They are not full child tasks and do not have independent
archive, trash, priority, due date, project, tag, reminder, or recurrence
state.

Allowed checklist changes:

- Active and completed parent tasks can add, edit, complete/reopen, reorder,
  and delete checklist rows.
- Archived parent tasks keep checklist rows visible for review, but checklist
  changes are rejected until the task is unarchived.
- Trashed parent tasks are not reachable through the normal detail page, and
  direct checklist actions are rejected by the same `Update` lifecycle guard.

Deleting a checklist item hard-deletes that contained row and resequences the
remaining checklist positions. Deleting or archiving the parent task does not
delete checklist rows; the parent can be restored with its checklist intact.
Only a future permanent parent force-delete would remove the rows through the
database cascade, and force-delete remains disabled by policy.

Checklist changes dispatch `TodoChecklistChanged` for the later
activity-history step.

## Validation

- Title: `required|string|max:120`, normalized (trimmed) before persistence.
  `TodoForm` validates the Livewire input, `TodoData::fromArray()` trims the
  normal form path, and `CreateTodo` trims again at the write boundary so a
  manually constructed DTO cannot persist wrapper whitespace. Step 023 applies
  the same action-level trim to `UpdateTodo`.
- Tab/filter input is validated against `TodoStatus::tabValues()`; an unknown
  tab falls back to Active and never widens the query scope.

## UI states covered

Empty state per tab (active / completed / archived / trash), inline validation errors,
success/warning toasts for every action, `wire:confirm` on destructive actions
(delete, clear completed), state-aware row actions (complete only on active
rows, reopen only on completed rows, no complete/edit on archived rows,
unarchive only on archived rows, restore only on trash rows), and a status
badge per row. All user-facing text is
translatable via `lang/en/todos.php`.

## Intentionally not built yet

Permanent delete controls, templates, reminders, recurring tasks,
collaboration, comments, attachments, imports/exports, and automation
processors remain scheduled for their own future steps. Permanent deletion
needs a stricter product and authorization design before it should exist.

## What Step 027 should build next

The next step should recheck the full lifecycle state machine now that Active,
Completed, Archived, and Trash are all visible buckets. It should keep using
the ownership scope, action/query/policy split, event boundaries, and
translation structure established here.
