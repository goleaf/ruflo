# Authorization & Private Workspace Model

This document specifies the access-control model for the Todo application. It
is the contract every future feature (projects, tags, reminders, recurring
tasks, dashboard, search, filters, bulk actions, collaboration) must follow.
If a change cannot satisfy these rules, the change is wrong, not the rules.

## Core invariant

> One user must never view, search, count, create-for, edit, complete, reopen,
> archive, unarchive, delete, restore from trash, or otherwise infer another user's private todo
> data — unless a future collaboration feature grants it through an explicit,
> authorized, tested permission.

Private by default. Shared only by explicit permission. Every shared access
re-checked. Every shared access testable.

## The workspace boundary

There is no separate `Workspace` model yet. **The owning `User` is the
workspace.** Ownership is represented by a `user_id` foreign key on each
todo-related resource.

This is deliberate: it keeps the current product single-user-private while
leaving a clean seam. When shared workspaces arrive, the ownership boundary
moves from "the owner user" to "an authorized workspace member", and the
checks below change in exactly one place each (the policy and the ownership
scope) rather than across the whole codebase.

## Where the rules live (single sources of truth)

| Concern | Location | Rule |
| --- | --- | --- |
| Ownership scoping | `App\Models\Concerns\BelongsToUser` (`scopeOwnedBy`, `isOwnedBy`) | The only way to scope a query or check ownership. |
| Read boundary | `App\Queries\Todos\TodoListQuery` | The only place todos are read for the UI. Always owner-scoped. |
| Board read boundary | `App\Queries\Todos\TodoBoardQuery` | Owner-scoped Kanban columns for active, completed, and archived tasks. |
| Checklist read boundary | `App\Queries\Todos\TodoChecklistItemListQuery` | Owner-scoped checklist rows for one already scoped parent task. |
| Dependency read boundary | `App\Queries\Todos\TodoDependencyQuery` | Owner-scoped dependency rows and blocker candidates for already scoped tasks. |
| Cleanup read boundary | `App\Queries\Todos\TodoCleanupQuery` | Owner-scoped active cleanup smart views for stale, unplanned, blocked, and risky tasks. |
| Automation read boundary | `App\Queries\Automation\AutomationRuleQuery` | Owner-scoped automation rules with latest run reports. |
| Reminder read boundary | `App\Queries\Reminders\ReminderListQuery` | Owner-scoped reminders, task options, and summary counts. |
| Notification read boundary | `App\Queries\Notifications\NotificationInboxQuery` | Owner-scoped database notifications, read-state filters, and scoped mutation lookup. |
| Daily dashboard read boundary | `App\Queries\Dashboard\DailyDashboardQuery` | Owner-scoped daily counts for due work, reminders, unread notifications, and tracked time. |
| Recurrence read boundary | `App\Queries\Todos\TodoRecurrenceRuleQuery` | Owner-scoped recurrence rules, generated occurrences, exceptions, and active task options. |
| Template read boundary | `App\Queries\Todos\TodoTemplateListQuery` | Owner-scoped reusable task/project/checklist/routine templates. |
| Inbox read boundary | `App\Queries\Todos\TodoInboxQuery` | Owner-scoped active captured tasks waiting for triage. |
| Focus read boundary | `App\Queries\Todos\TodoFocusQuery` | Owner-scoped active urgent/overdue/due-today/high-priority focus set. |
| Saved view read boundary | `App\Queries\Todos\SavedTodoViewListQuery` | Owner-scoped saved task-view listing and lookup. |
| Per-action decisions | `App\Policies\TodoPolicy` | The only place "may this user do this?" is answered. |
| Policy binding | `#[UsePolicy(...Policy::class)]` on current private models | Explicit, greppable mapping — not naming-convention magic. |
| Mutations | `App\Actions\Todos\*` | Assign ownership from the authenticated user; never from request input. |
| Dashboard summary | `App\Queries\Dashboard\DailySummaryQuery` | Counts tasks, trash, active projects, goals, habits, and tags through owner-scoped queries only. |

Do not scatter authorization across components, views, or query callers.
Reuse these.

## Ownership assignment

Ownership is assigned by backend logic only:

- New todos are created through the owning relationship
  (`$user->todos()->create(...)` inside `CreateTodo`).
- `user_id` is **not** in the model's `#[Fillable]` list, so mass assignment
  cannot set or change it. A test (`TodoOwnershipTest`) proves a submitted
  `user_id` is ignored.
- Ownership transfer is **not** a feature. If it ever becomes one, it must be a
  dedicated, authorized action with activity tracking — never a casual update.

## Query scoping

Every read of todo data must start from `TodoListQuery` (or apply `ownedBy`
directly). Consequences that are mandatory, not optional:

- Lists, single-record lookups, trash lookups, and counters are all
  owner-scoped.
- Client-supplied IDs are untrusted. `findVisibleFor()` and `findTrashedFor()`
  resolve them through owner-scoped queries, so another user's ID returns **not
  found** — the record's existence never leaks.
- Aggregates (the remaining/completed summary) are computed inside the scope,
  so counters can never include another user's tasks.
- URL filter IDs are re-checked against the owner before they become query
  predicates. A foreign, archived, or missing project/tag filter resolves to an
  empty result instead of being ignored or applied as another user's ID.
- Related project and tag labels are constrained to the same owner before
  eager loading. Normal actions already prevent foreign links, but malformed
  legacy rows or manual database edits still must not leak names in the UI.

The safest query is one that never sees unauthorized data in the first place.

## Authorization at the action boundary

The Livewire component authorizes **before** delegating to an action:

- `viewAny` / `create` / `clearCompleted` / `bulk*` — class-level abilities,
  allowed for any authenticated user (they only ever touch that user's own
  workspace).
- `view` / `update` / `complete` / `reopen` / `archive` / `unarchive` /
  `delete` / `restore`
  — per-record abilities, owner-only, returning `denyAsNotFound()` so forbidden
  access is indistinguishable from a missing record.
- `complete` and `reopen` are separate policy abilities. Step 024 also gives
  each transition its own Livewire method and action class, while the row
  checkbox remains a compact UI affordance.
- `forceDelete` — disabled for everyone. Permanent deletion is not a feature
  yet; when designed it must be protected more strictly than soft delete.
- `bulkRestoreDeleted` — class-level ability for restoring selected trash rows
  after each selected id validates as one of the current user's deleted tasks.
- Saved task views use `SavedTodoViewPolicy`: `viewAny` and `create` are
  available to authenticated users for their own workspace, while `view`,
  `update`, and `delete` are owner-only and hide foreign ids as not found.
- Checklist rows use `TodoChecklistItemPolicy`: `viewAny` and `create` are
  available to authenticated users, while per-row `view`, `update`, and
  `delete` are owner-only and hide foreign ids as not found. Checklist actions
  also authorize the parent task update before mutating, so a malformed row
  cannot use one user's `user_id` to change another user's task.
- Dependency rows use `TodoDependencyPolicy`: `viewAny` and `create` are
  available to authenticated users, while per-row `view`, `update`, and
  `delete` are owner-only and hide foreign ids as not found. Add/remove actions
  resolve blocker ids through `TodoDependencyQuery`, authorize the parent task,
  and never trust a submitted dependency id until it has been re-scoped to the
  current user and current task.
- Templates use `TodoTemplatePolicy`: `viewAny` and `create` are available to
  authenticated users for their own workspace; `view`, `update`, `delete`, and
  `instantiate` are owner-only and hide foreign ids as not found. Shared
  visibility is currently a label stored for future collaboration; it does not
  grant access to another user before memberships and roles exist.
- Reminders use `ReminderPolicy`: `viewAny`, `create`, and `process` are
  available to authenticated users for their own workspace; `view`, `update`,
  and `delete` are owner-only and hide foreign ids as not found. Restore and
  force delete remain unavailable.
- Recurrence rules use `TodoRecurrenceRulePolicy`: `viewAny` and `create` are
  available to authenticated users for their own workspace; per-row `view`,
  `update`, and `delete` are owner-only and hide foreign ids as not found.
  Save, toggle, and delete actions also re-check that the related task belongs
  to the current user and is active before changing rule state.
- Recurrence exceptions use `TodoRecurrenceExceptionPolicy`: `viewAny` and
  `create` are available to authenticated users for their own workspace;
  per-row `view`, `update`, and `delete` are owner-only and hide foreign ids as
  not found. Skip, edit-marker, and move actions never trust a submitted
  occurrence id until it has been re-queried as the current user's generated
  occurrence. Occurrence-versus-series edit actions use the same owner-scoped
  generated occurrence lookup before mutating one occurrence or the source
  series plus future unedited generated rows.

Backend authorization is the real security. Frontend hiding of buttons is UX
only and is never sufficient.

## Route protection

All private todo routes live behind `['auth', 'verified']` in
`routes/web.php`. Guests are redirected to login; unverified users to
verification. The `User` model implements Laravel's `MustVerifyEmail`
contract so the `verified` middleware is active instead of being a no-op.
There are no public routes that touch private data. Future todo routes must
join the same protected group - never a standalone public route "just for
testing".

Settings routes use the same public/private boundary:

- `settings/profile` is authenticated and can render for unverified users so
  they can see and manage their verification status.
- `settings/appearance` is authenticated and verified.
- `settings/security`, `settings/setup`, and `settings/maintenance` are
  authenticated, verified, and password-confirmed.
- `settings/maintenance` also requires the admin-only
  `access-maintenance-center` gate.

Task detail pages use the same private route boundary. `todos.show` is a
class-based Livewire page behind `auth` and `verified`, accepts only a numeric
task id, resolves the record through `TodoListQuery::findVisibleFor()`, and
locks the public `todoId` property. A guessed or foreign id returns not found
without rendering the foreign title, project, tag, priority, or due date.

Project detail pages use the same pattern. `projects.show` is a class-based
Livewire page behind `auth` and `verified`, accepts only a numeric project id,
resolves the record through `ProjectListQuery::findVisibleFor()`, and locks the
public `projectId` property. A guessed or foreign id returns not found without
rendering the foreign project name or task list.

Task template pages use the same private route boundary. `todos.templates` is a
class-based Livewire page behind `auth` and `verified`, lists templates through
`TodoTemplateListQuery`, and resolves every submitted template id through
`findFor($user, $id)` before edit, delete, or instantiate actions run.

The quick capture Inbox uses the same private route boundary. `todos.inbox` is
a class-based Livewire page behind `auth` and `verified`, lists active captured
tasks through `TodoInboxQuery`, and resolves every submitted task id through
`findFor($user, $id)` before triage actions run. A foreign, completed,
archived, trashed, or already-triaged task id returns not found from the Inbox
surface.

Focus mode uses the same private route boundary. `todos.focus` is a class-based
Livewire page behind `auth` and `verified`, lists only active owner-scoped
urgent, overdue, due-today, and high-priority tasks through `TodoFocusQuery`,
and resolves every selected task id through `findFor($user, $id)` before
complete, defer, or snooze actions run.

Pomodoro focus sessions use the same owner boundary. `PomodoroSession` uses
`BelongsToUser`, resolves `PomodoroSessionPolicy`, and is always linked to a
private task. Focus-page timer actions read active sessions through
`PomodoroSessionQuery`, authorize session updates, and deny foreign sessions as
not found. Starting a session still resolves the linked task through
`TodoFocusQuery` so a forged task id cannot start a timer for non-focus work.

Time tracking uses the same private route and owner boundary. `todos.time` is a
class-based Livewire page behind `auth` and `verified`. `TimeEntry` uses
`BelongsToUser`, resolves `TimeEntryPolicy`, and all reads flow through
`TimeEntryQuery`. Manual and timer actions resolve submitted task/project ids
back to the current user before writing; foreign, archived, trashed, or
otherwise untrackable task ids do not create time entries.

Blocked tasks use the same private route and owner boundary. `todos.blocked` is
a class-based Livewire page behind `auth` and `verified`. It reads active
blocked tasks through `TodoListQuery::blockedFor()`, eager-loads only
current-user project/tag/dependency labels, and links back to private task
detail pages. Dependency add/remove actions live on `todos.show`, where the
task is already resolved through `TodoListQuery::findVisibleFor()`.

Cleanup smart views use the same private route and owner boundary.
`todos.cleanup` is a class-based Livewire page behind `auth` and `verified`. It
reads active tasks through `TodoCleanupQuery`, eager-loads only current-user
project/tag/dependency labels, treats invalid view parameters as an empty result,
and never trusts URL search/sort state to widen the owner scope.

Automation rules use the same private route and owner boundary.
`todos.automations` is a class-based Livewire page behind `auth` and `verified`.
`AutomationRule` and `AutomationRuleRun` use `BelongsToUser` and explicit
policies. Rule ids submitted to toggle, test, or run actions are resolved
through `AutomationRuleQuery::findFor($user, $id)` before authorization.
Automation runs mutate only tasks found through the current user's owned task
relationship, and foreign rule ids resolve as not found.

Reminders use the same private route and owner boundary. `todos.reminders` is a
class-based Livewire page behind `auth` and `verified`. Reminder rows are listed
through `ReminderListQuery`, schedule input validates the selected task through
`OwnedTodo`, and due processing starts from `$user->reminders()` before any
notification is created. Notification action URLs route to protected task pages,
where the task is resolved again through owner-scoped lookup.

The notification center uses the same private route and owner boundary.
`notifications.inbox` is a class-based Livewire page behind `auth` and
`verified`. Notification rows are listed through `NotificationInboxQuery`, which
scopes by the authenticated user's notifiable type and id. Read/unread actions
resolve submitted notification ids through that same query before changing
`read_at`, so another user's notification id is treated as not found. Action
URLs are same-host display hints only. Known task links are pre-checked against
the current user's task scope, and destination routes still re-authorize the
target private record.

The dashboard daily summary uses the same private route and owner boundary.
`dashboard` is a class-based Livewire page behind `auth` and `verified`. The
daily card reads through `DailyDashboardQuery`, which composes owner-scoped
task, reminder, time-entry, and notification queries before rendering counters.
It never reads another user's due tasks, blockers, reminders, unread
notifications, or tracked time.

Recurring task rules use the same private route and owner boundary.
`todos.recurring` is a class-based Livewire page behind `auth` and `verified`.
Rules are listed through `TodoRecurrenceRuleQuery`, task selection validates
with `OwnedActiveTodo`, and save/toggle/delete actions authorize both the rule
and related task before mutation. Task detail recurrence controls use the same
actions and show locked context for completed, archived, or deleted tasks
instead of mutating inactive work.

Recurring exception controls use the same page and owner boundary. Generated
occurrence ids submitted to skip, edit-marker, or move actions are resolved
through owner-scoped generated-task queries before any write. A moved occurrence
updates only that generated task and records the original date in
`todo_recurrence_exceptions`; skipped occurrences are soft-deleted and remain
counted as existing rows for duplicate prevention.

## Error behavior (no leakage)

- Forbidden private records resolve as **not found** (404-style), never
  "forbidden — this belongs to someone else".
- Error and validation messages are translatable and never echo another user's
  task content.
- Page titles, navigation, counters, and (future) search/filter dropdowns must
  only ever reflect the current user's workspace.

## Preparation for later steps

These are documented now so later steps inherit the model instead of bolting
it on:

- **Dashboard** — every current widget/counter uses
  `DailyDashboardQuery::for($user)`, `DailySummaryQuery::for($user)`, or
  `TodoListQuery::summaryFor($user)`; if cached later, cache keys must be
  per-user so data never mixes.
- **Search & filters** — ownership is applied at the query level before any
  text/status/priority filtering; invalid filter input is validated and must
  never widen the scope. Tampered numeric project/tag filters return a safe
  empty state. Saved views store only normalized filter/sort criteria and
  applying them still flows through the same owner-scoped query boundary.
- **Kanban board** — board cards are read through `TodoBoardQuery`; card moves
  resolve the task through `TodoListQuery::findVisibleFor()`, validate target
  columns with `BoardStatus`, validate target projects with `OwnedActiveProject`,
  and delegate lifecycle changes to existing authorized actions.
- **Checklists** — task detail pages resolve the parent through
  `TodoListQuery::findVisibleFor()` and resolve each checklist row through
  `TodoChecklistItemListQuery::findFor($user, $todo, $itemId)`. That means a
  submitted checklist id must belong to both the current user and the current
  task before it can be toggled, edited, moved, or deleted.
- **Dependencies** — task detail pages resolve the parent through
  `TodoListQuery::findVisibleFor()` and resolve each dependency row or blocker
  candidate through `TodoDependencyQuery`. Submitted blocker ids must belong to
  the current user, be active, not duplicate an existing edge, and not create a
  cycle before they can be attached.
- **Templates** — template create/edit/delete/use flows stay owner-scoped.
  Instantiation creates a normal task through `CreateTodo`, resolves or creates
  an owner-scoped active project by template project name, and adds contained
  checklist rows through `CreateTodoChecklistItem`. A foreign private or
  shared-labeled template cannot be used.
- **Inbox** — capture creates a normal owned todo through `CreateTodo`; triage
  resolves through `TodoInboxQuery`, authorizes update, delegates organization
  changes to `UpdateTodo`, and clears `inbox_captured_at` only for an active
  owner-scoped inbox row.
- **Focus mode** — focused task actions resolve through `TodoFocusQuery` before
  authorization. Complete delegates to `CompleteTodo`; defer and snooze
  delegate to `RescheduleFocusedTodo`, which authorizes update and applies the
  existing lifecycle state-machine guard before changing the due date.
- **Pomodoro sessions** — timer mutations resolve active rows through
  `PomodoroSessionQuery`, authorize `PomodoroSessionPolicy::update`, and never
  trust a frontend session or task id. Complete/defer/snooze task actions close
  only a linked active session owned by the current user.
- **Time entries** — manual/timer writes authorize `TimeEntryPolicy`, resolve
  context through `TimeEntryQuery`, and reject foreign project/task ids. Only
  one active timer is allowed per user, and completed Pomodoro sessions create a
  single linked time entry through a unique `pomodoro_session_id`.
- **Bulk actions** — never trust a submitted set of IDs. Re-scope every
  selected ID to the owner and authorize each actionable record before acting;
  a foreign ID in the Livewire payload is rejected at validation, while direct
  action calls re-scope and report skipped ids through `BulkActionResult`
  instead of processing them. Trash bulk restore uses the trashed-owner
  validation path.
- **Activity history** — visible only to users who can access the related
  record; scoped exactly like the record itself.
- **Notifications/reminders** — Step 054 stores due reminder notifications in
  the database for the owning user only. A notification is a message, not a
  permission, so opening its link re-checks authorization.
- **Collaboration** — when added, the policy and `BelongsToUser` boundary
  expand to "authorized member" in one place each; do not hardcode
  "exactly one user" assumptions elsewhere.
- **Roles** — viewer/editor/manager/owner roles do not exist yet. Until the
  collaboration/member steps introduce a membership model, policy tests cover
  owner, non-owner, and the existing admin-only maintenance gate.

## Testing requirements

Access-control tests are mandatory and must use **at least two users**.
`TodoOwnershipTest` and `TodoTest` already cover: policy resolution, owner
allow / non-owner deny for every per-record ability, not-found leakage
behavior, mass-assignment refusal, owner-scoped queries and counters, and
guest redirects. Every future todo capability must add the matching
cross-user denial test before it is considered done.

`PrivateWorkspaceModelTest` locks the Step 017 and Step 054 contracts:
todo-related private models must use the shared owner concern, private policies
must hide foreign records as not found, dashboard counts must be user-scoped,
malformed cross-user project/tag links must not hydrate foreign labels, and
reminder task links must stay inside the owner boundary.

`OwnershipQueryScopingTest` locks the Step 018 contract: project/tag picker
queries are owner-scoped, tampered project/tag filters are empty rather than
foreign-scoped, edit-form tag hydration uses the scoped query result, and
server-assigned Livewire edit IDs are locked.

`AuthorizationPoliciesTest` locks the Step 019 and Step 054 contract: every
current private resource resolves to an explicit policy, todo lifecycle/bulk
abilities are named and authorized before mutation, unsupported destructive
abilities are denied, and reminders expose only owner web-processing abilities.

`GuestRouteProtectionTest` locks the Step 020 contract: guests are redirected
from every private app page, unverified users are redirected from verified
routes, profile settings remain reachable to authenticated unverified users,
sensitive settings require password confirmation, maintenance remains
admin-only, protected route middleware cannot be removed silently, and the demo
login panel never renders stored password hashes.

`TaskPrivateViewsTest` locks the Step 022 contract: task detail pages redirect
guests and unverified users, render only the owner's task data, return not
found for foreign ids, expose only current-user detail links in the task list,
and keep the detail component on locked IDs plus owner-scoped queries.

`TaskCompletionReopeningTest` locks the Step 024 contract: complete and reopen
use separate action classes, events, policy checks, translated labels, and
owner-scoped Livewire methods; archived tasks are rejected; idempotent calls do
not duplicate activity; bulk completion reuses the same complete transition.

`TaskArchiveRestoreTest` locks the Step 025 contract: archive and unarchive use
explicit action classes, events, policy checks, translated labels, and
owner-scoped Livewire methods; completion state is preserved; idempotent calls
do not duplicate activity; bulk archive/unarchive reuses the same single-task
transitions.

`TaskDeletionTrashTest` locks the Step 026 contract: delete and restore from
Trash use explicit action classes, events, policy checks, translated labels,
owner-scoped visible/trash query methods, and custom selected-id validation;
bulk delete/restore reuse single-task transitions; foreign trash ids are hidden
as not found; permanent delete remains denied and absent from the UI.

`ProjectDetailTest` locks the Step 028 contract: project detail routes are
guest/verification protected, project ids are owner-scoped before rendering,
foreign project names and tasks are hidden as not found, archived projects
remain readable, project badge links are scoped to current-user data, and empty
project states are translated.

`SavedTodoViewTest` locks the Step 038 contract: saved views are listed,
created, applied, and deleted only for their owner; blank and duplicate names
are rejected; stale foreign project criteria cannot leak names or widen task
results; and foreign saved-view ids resolve as not found.

`BulkSelectionActionTest` locks the Step 039 contract: visible-page selection,
clear selection, result counts, skipped direct-action ids, and the Flux
bulk-delete confirmation modal all stay owner-scoped and translated.

`KanbanBoardTest` locks the Step 040 contract: the board route is protected,
foreign cards never render, lifecycle moves preserve existing transition rules,
project moves accept only owned active projects, invalid columns fail
validation, and foreign task ids resolve as not found.

`CalendarViewTest` locks the Step 041 contract: the calendar route is
protected, month reads are owner-scoped, completed/archived/trashed/foreign
tasks do not render in the month grid, invalid month input cannot widen scope,
and reminder/recurrence placeholders do not expose placeholder reminder rows.

`TaskChecklistTest` locks the Step 042 contract: task detail pages render only
the current task's checklist rows, progress is owner-scoped, Livewire checklist
actions re-resolve submitted item ids against the current task, action-layer
validation rejects invalid direct calls, archived parent tasks keep checklists
visible but locked, and invalid movement directions cannot mutate ordering.

`TaskTemplateTest` locks the Step 043 contract: template pages are protected,
owner templates render without foreign templates, create/edit/delete/use flows
are owner-scoped, invalid template data is rejected in Livewire and direct DTO
calls, foreign and missing template ids resolve as not found, and direct
instantiation of another user's template is denied.

`GoalMilestoneTest` locks the Step 046 contract: the goals page is protected,
owner goals render without foreign goals, progress counts only real linked
tasks and checked-in milestones, goal creation accepts only owned active
projects, milestone check-ins cannot be spoofed for another user, and task links
are resolved through owner-scoped goals, milestones, and active/completed tasks.

`HabitTrackerTest` locks the Step 047 contract: the habits page is protected,
owner habits render without foreign habits, daily and weekly progress/streaks
come from real check-in rows, habit creation accepts only owned active goals,
today check-ins cannot be spoofed for another user, archived habits reject
check-ins, and task links are resolved through owner-scoped habits and
active/completed tasks.

`AutomationRulesTest` locks the Step 052 contract: automation rules are listed,
created, toggled, tested, and run only for their owner; dry runs do not mutate
tasks; disabled runs change nothing; bounded chunks can be retried to resume
remaining work; archive automation touches only old owned completed tasks; and
foreign rule ids resolve as not found.

`ManualWebProcessTest` locks the Step 053 processing contract: reusable
processors must build owner-scoped queries, dry runs must not mutate records,
bounded chunks must report remaining work, and retry/resume must happen by
re-running the same authenticated browser action. The engine does not create a
global data scope; each feature-owned `ManualWebProcess` implementation is
responsible for starting from an authorized owner boundary.

`ReminderSystemTest` locks the Step 054 contract: owner-scoped reminder page
rendering, scheduling validation, due reminder processing, chunk resume,
database notifications, disabled preferences, dashboard-triggered processing,
and multi-user isolation.

`RecurringTaskRuleTest` and `RecurringTaskRulesTest` lock the Step 057
contract: recurrence rules are listed and mutated only for their owner,
foreign/inactive task ids fail safely, schedule payloads are normalized through
custom rules, task detail controls update the same user/task rule without
duplicates, and inactive tasks keep recurrence context locked from mutation.

`RecurringOccurrenceGenerationTest` locks the Step 058 contract: recurrence
generation starts from owner-scoped enabled rules, processes bounded manual web
chunks, creates duplicate-safe private task rows, copies source task tags and
pending reminder offsets, and never generates tasks for another user's series.
