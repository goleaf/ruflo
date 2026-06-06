# Task Organization

Through Step 053, the private task lifecycle is extended into a usable productivity system:
projects, tags, priorities, due dates, search, filters, sorting, and bulk
actions, calendar/board/focus views, contained checklists, templates, a quick
capture Inbox, time tracking, task dependencies, cleanup smart views, and
browser-triggered automation rules backed by the reusable manual web-processing
engine. Everything here is
owner-scoped on top of the model in
[`authorization.md`](authorization.md) and the lifecycle in
[`task-lifecycle.md`](task-lifecycle.md).

## What was added

| Concept | Storage | Ownership |
| --- | --- | --- |
| **Project** (a.k.a. list) | `projects` table; `todos.project_id` (nullable FK) | `projects.user_id`, private |
| **Tag** | `tags` table; `tag_todo` pivot | `tags.user_id`, private, unique name per user |
| **Priority** | `todos.priority` (enum string) | n/a — `App\Enums\Priority` (low/normal/high/urgent) |
| **Due date** | `todos.due_date` (date) | n/a |
| **Saved view** | `saved_todo_views` table; normalized criteria JSON | `saved_todo_views.user_id`, private |
| **Checklist item** | `todo_checklist_items` table; `todo_id`, ordered `position`, completion fields | `todo_checklist_items.user_id`, private, contained by parent task |
| **Inbox capture** | `todos.inbox_captured_at` nullable timestamp | `todos.user_id`, private |
| **Task dependency** | `todo_dependencies` table; waiting task plus blocker task ids | `todo_dependencies.user_id`, private, both tasks must belong to the same owner |
| **Automation rule** | `automation_rules` and `automation_rule_runs` tables | `automation_rules.user_id` and `automation_rule_runs.user_id`, private |

## Subtasks and checklists

Step 042 adds contained checklist rows on task detail pages. They are subtasks
for planning and progress, but they are not full tasks: they do not have their
own project, tags, due date, priority, archive state, trash state, reminders,
or recurrence rules.

Checklist reads use `TodoChecklistItemListQuery`, scoped by both the current
user and the already owner-resolved parent task. Checklist mutations use
`CreateTodoChecklistItem`, `UpdateTodoChecklistItem`, `ToggleTodoChecklistItem`,
`MoveTodoChecklistItem`, and `DeleteTodoChecklistItem`. The Livewire detail page
resolves every submitted checklist item id through that query before
authorizing and delegating to an action.

Behavior:

- Items are ordered by `position` and can be moved up or down with Livewire
  buttons. Deleting an item resequences the remaining rows.
- Progress is computed from the current task's checklist rows and rendered as a
  Flux progress bar plus a translated count.
- Active and completed parent tasks can change checklist items. Archived tasks
  keep their checklist visible for review but show a locked state; they must be
  unarchived before checklist edits. Trashed tasks are not reachable through the
  normal detail page.
- Parent task archive/trash preserves checklist rows. A future permanent
  `forceDelete` of the parent would cascade-delete rows at the database level,
  but permanent task deletion remains disabled.
- Checklist item titles are squished, limited to 120 characters, validated by
  `ChecklistItemTitle`, and also rejected at the action boundary if validation
  is bypassed.
- `TodoChecklistChanged` is dispatched for checklist changes so the later
  activity-history step can listen without changing checklist actions.

Seeded demo workspaces include realistic checklist rows on due, overdue,
upcoming, and archived tasks so `/todos/{id}` shows progress immediately on
`https://ruflo.test/`.

## Projects

- A task belongs to at most one project. A task with no project is valid and
  filterable ("No project").
- Projects can be **renamed**, **archived** (hidden from active pickers/filters,
  reversible), **restored**, or **deleted**. Deleting a project **never deletes its tasks** — the
  `project_id` FK is `nullOnDelete`, so the tasks fall back to "No project".
- Each project has an owner-scoped detail page at `projects.show`, implemented
  by `App\Livewire\Projects\Show`. Archived projects remain readable there so
  existing tasks can be reviewed, but archived projects are still excluded from
  assignment and filter pickers.
- `project_id` is **not** mass-assignable. `CreateTodo`/`UpdateTodo` set it
  directly only after re-scoping it to the user (`ResolvesTodoOrganization`),
  so a forged request can never attach a task to another user's project.
- Authorization: `ProjectPolicy` (owner-only `view`/`update`/`archive`/
  `restore`/`delete`; denials read as not-found).

## Tags

- Many-to-many with tasks. Tag names are normalized (squished + lower-cased) so
  "Work", "work", and " work " collapse to one label; a per-user unique
  constraint enforces it. Two different users may each own a tag named "work".
- Creating a tag uses `TagName` validation and `firstOrCreate`, so whitespace-only
  names are rejected and the same normalized name never fragments.
- Deleting a tag removes the pivot rows (cascade) but never the tasks.
- Tag ids on a task are re-scoped to the user before syncing — foreign tag ids
  are silently dropped.
- Rendered tag badges link to the existing `todos.index?tag=...` owner-scoped
  filter instead of a separate tag detail page. Bulk tag assignment remains
  deferred to the later bulk workflow steps.
- Authorization: `TagPolicy` (owner-only; not-found denials).

## Priority

`App\Enums\Priority`: Low / Normal / High / Urgent, each with a translatable
label, a badge color, and a sort `weight()`. Stored as the enum's string value
and cast on the model.

Step 030 tightened priority handling so the enum is the single source for:

- Livewire create/edit validation, using Laravel's `Rule::enum(Priority::class)`.
- Direct DTO normalization. Missing priority still defaults to Normal, but an
  invalid provided value raises a translated validation error instead of being
  silently coerced.
- Priority sorting. `Priority::sortCaseSql()` generates the bounded SQL `CASE`
  expression from each enum case and its `weight()`, so query ordering cannot
  drift from the displayed priority meanings.

Priority filters remain owner-scoped through `TodoListQuery::filtered()`, and
priority badges continue to use translated labels and Flux badge colors from
the enum.

## Due dates & date buckets

Dates are stored as date-only `Y-m-d` values in `todos.due_date` and compared in
the configured **application timezone**. The current local/test configuration is
UTC. Per-user timezone preferences are intentionally deferred to the later
language/timezone settings step, so due-date buckets do not currently shift per
account.

Step 031 tightened due-date handling:

- Livewire create/edit forms use the reusable `DueDate` validation rule so only
  canonical `Y-m-d` date strings are accepted.
- `TodoData::fromArray()` normalizes empty due dates to `null` and rejects
  invalid provided dates with a translated validation error instead of relying
  on broad PHP date parsing.
- `Todo` exposes active-only `isOverdue()`, `isDueToday()`, and `isUpcoming()`
  helpers that match the query scopes.

The buckets, all **active-only** (completed/archived/deleted tasks are never
overdue/today/upcoming):

- **Due today** — `due_date == today`, active.
- **Overdue** — `due_date < today`, active. Completing or archiving a task
  stops it from being overdue.
- **Upcoming** — `due_date > today`, active.

These live as model scopes (`scopeDueToday`/`scopeOverdue`/`scopeUpcoming`) plus
the matching model helpers, and feed the summary's `overdue` counter.

## Search, filters, sorting

All of this is centralized in `App\Queries\Todos\TodoListQuery::filtered()`,
which takes a validated `TodoFilters` value object and always starts from the
owner scope. No filtering happens in the view or the component beyond building
the (sanitized) filter object.

- **Search** — case-insensitive `LIKE` on title, with LIKE wildcards (`%`, `_`)
  escaped via an `ESCAPE` clause so a search for `50%` matches the literal text
  instead of returning everything.
  Step 035 keeps this local and self-hosted instead of adding a paid or hosted
  search service. The URL-backed Livewire search term is squished, limited to
  120 characters before querying, debounced in the UI, and shown as a translated
  active filter chip. Search composes with pagination and reset behavior.
- **Filters** — lifecycle tab (active/completed/archived), project (or "none"),
  tag, priority, and due bucket. Every filter value is sanitized in the
  component's `buildFilters()` before it reaches the query: unknown sort values
  fall back to safe defaults, and invalid lifecycle/priority/due values are
  carried as invalid filter state so they can never widen scope.
  Numeric project/tag filters are re-checked inside `TodoListQuery::filtered()`;
  foreign, archived, or missing ids return an empty result rather than applying
  another user's id or falling back to an unfiltered list. Step 035 also treats
  non-numeric project/tag URL values as invalid filters so they reach that same
  empty-result path instead of being silently ignored.
  Step 036 extends that empty-result behavior to invalid lifecycle, priority,
  and active-tab due-bucket values. Valid filters compose across project, tag,
  priority, due bucket, search, sorting, and pagination; invalid filter state is
  carried by `TodoFilters::hasInvalidFilter` so the query object, not the view,
  decides the result is empty.
- **Sorting** — `created`, `updated`, `due` (nulls last), `priority` (by weight
  via a bounded `CASE`), `project` (by owned project name, ungrouped tasks last),
  or `title`, each asc/desc. The sort key is validated against
  an allow-list, so a tampered `?sort=` string can never inject SQL — it falls
  back to `created`. Step 037 adds deterministic tie-breakers for every sort
  path so pagination remains stable when tasks share the same title, date,
  project, priority, or timestamp. Non-default and tampered sort/direction URL
  state renders as translated Flux chips so users can see the current ordering
  and reset it with the rest of the filter panel.
- **Pagination** — the list is paginated (15/page) via `WithPagination`; it
  never loads an unbounded result set.
- **Saved views** — Step 038 stores a user's current tab, search, project, tag,
  priority, due bucket, sort, and direction as normalized criteria on
  `saved_todo_views`. The payload never stores task results or another user's
  resource names. Applying a saved view writes the bounded criteria back through
  the same Livewire URL state and `TodoListQuery` sanitizer used by normal
  filtering, so stale or foreign project/tag ids produce the existing empty
  owner-scoped result instead of widening the list. Saved view names are unique
  per user and validated by `SavedViewName`.

Filters, search, sort, and pagination compose: changing any of them resets the
page and clears the bulk selection. Active filters render as translated Flux
badges with one clear action so users can see when a search or filter is
constraining the list.

## Today view

Step 032 adds a dedicated `todos.today` Livewire page for active tasks due
today. It is protected by the same `auth` and `verified` middleware as the main
todo workspace and uses `TodoListQuery::todayFor()` so reads stay owner-scoped,
active-only, and eager-loaded for project/tag badges.

The Today page:

- displays only active current-user tasks where `due_date` is today in the app
  timezone,
- excludes overdue, upcoming, completed, archived, trashed, and foreign tasks,
- links each task to its private detail page,
- includes project/tag badges that reuse existing owner-scoped links,
- offers a complete action limited through `TodoListQuery::findTodayFor()`,
- links back to the main task workspace with the equivalent `due=today` filter.

The dashboard workspace card now links directly to Today for quick review while
keeping the full task workspace one click away.

## Overdue view

Step 033 adds a dedicated `todos.overdue` Livewire page for active tasks with a
due date before today. It is protected by the same `auth` and `verified`
middleware as the main todo workspace and uses `TodoListQuery::overdueFor()`
so reads stay owner-scoped, active-only, and eager-loaded for project/tag
badges.

The Overdue page:

- displays only current-user active tasks where `due_date` is before today in
  the app timezone,
- excludes today, upcoming, completed, archived, trashed, and foreign tasks,
- links each task to its private detail page,
- includes project/tag badges that reuse existing owner-scoped links,
- offers a complete action limited through `TodoListQuery::findOverdueFor()`,
- links back to the main task workspace with the equivalent `due=overdue`
  filter.

The dashboard workspace card links to Overdue beside the Today and full Todo
workspace shortcuts.

## Upcoming view

Step 034 adds a dedicated `todos.upcoming` Livewire page for active tasks with
a due date after today. It is protected by the same `auth` and `verified`
middleware as the main todo workspace and uses `TodoListQuery::upcomingFor()`
so reads stay owner-scoped, active-only, and eager-loaded for project/tag
badges.

The Upcoming page:

- displays only current-user active tasks where `due_date` is after today in
  the app timezone,
- excludes overdue, today, no-due-date, completed, archived, trashed, and
  foreign tasks,
- links each task to its private detail page,
- includes project/tag badges that reuse existing owner-scoped links,
- offers a complete action limited through `TodoListQuery::findUpcomingFor()`,
- links back to the main task workspace with the equivalent `due=upcoming`
  filter.

The dashboard workspace card links to Upcoming beside Today, Overdue, and the
full Todo workspace shortcuts.

## Focus mode and Pomodoro sessions

Step 045 adds `todos.focus`, a protected class-based Livewire page for working
from a short set of important active tasks.

- Focus reads use `TodoFocusQuery`; the page never queries tasks directly.
- The set is derived from existing private task state: all active urgent tasks
  are included first, then the remaining slots are filled with active overdue,
  due-today, and high-priority tasks until the normal target size of 5 is met.
- Urgent tasks are never hidden to preserve safety. If a user has more than 5
  active urgent tasks, the focus set grows beyond 5 instead of dropping urgent
  work.
- Quick complete uses `CompleteTodo`; defer and snooze use
  `RescheduleFocusedTodo` to move the due date to tomorrow or three days from
  today without changing owner, project, tags, priority, or lifecycle.
- Every submitted task id is resolved through `TodoFocusQuery::findFor()`
  before action authorization. Foreign tasks, archived tasks, completed tasks,
  trashed tasks, and active tasks outside the current focus set return not
  found from this page.
- Step 048 replaces the non-persisted demo timer with owner-scoped
  `pomodoro_sessions` linked to a focus task. Sessions store duration, status,
  elapsed seconds, start/resume timestamps, and close timestamps for complete or
  abandoned sessions.
- The Pomodoro controls remain on the existing Focus page. Users choose a 15,
  25, or 50 minute duration, start a session for the selected focus task,
  pause/resume it, complete it, or abandon it. The browser ticks the visible
  countdown while Livewire actions persist every state transition.
- Completing a focused task completes an active Pomodoro session linked to that
  task. Deferring or snoozing a focused task abandons the linked active session
  because the task has intentionally moved out of the current work slot.
- `PomodoroDuration` validates the allowed duration options and
  `StartPomodoroSession` also rejects starting a second active session for the
  same user.
- The page includes keyboard-backed selected-task actions (`C`, `D`, `S`) and
  timer toggle (`P` or Space).
- Focus mode is synchronous and bounded to one task action per request. It
  requires no cron, queue worker, supervisor, shell, Artisan command, paid
  service, chunk processor, retry loop, or server-side timer during normal
  hosted usage. Timer resume is the persisted active `pomodoro_sessions` row;
  exact second-by-second progress is only advanced while the page is open in the
  browser.

## Time tracking

Step 049 adds `todos.time`, a protected class-based Livewire page for manual
and timer-based tracking against private tasks and projects.

- Time tracking stores owner-scoped `time_entries` with optional task, project,
  and Pomodoro links, duration seconds, source, status, tracked date, start/stop
  timestamps, and notes.
- `TimeEntryQuery` is the single read/context boundary. It lists recent
  completed entries, computes today/week/total summaries, resolves one active
  timer, lists trackable non-archived/non-deleted tasks, and lists active
  projects for picker controls.
- Manual entries use `CreateManualTimeEntry` and `TimeEntryData`. They require
  a task or project context, a tracked date on or before today, and a duration
  from 1 to 1440 minutes through `TimeEntryDuration`.
- Timer entries use `StartTimeEntryTimer`, `StopTimeEntryTimer`, and
  `DiscardTimeEntryTimer`. The page allows one active timer per user; the active
  row persists so a browser refresh resumes the visible timer state.
- Completed Pomodoro sessions with at least one minute of elapsed work create
  one linked `TimeEntry` through `CreatePomodoroTimeEntry`. The
  `pomodoro_session_id` unique index keeps that integration idempotent.
- Deleting a completed time entry uses `DeleteTimeEntry`; running timers must be
  stopped or discarded before deletion.
- The Flux page includes manual entry fields, timer controls, recent entries,
  task/project links, translated badges, and keyboard-backed timer actions
  (`T` to start/stop, `X` to discard).
- Time tracking is synchronous and web-triggered. It requires no cron, queue
  worker, supervisor, shell, Artisan command, paid service, background timer, or
  server-side scheduler during normal hosted usage. Elapsed active-timer
  seconds are saved only when the user stops or discards the timer through the
  web UI.

## Blockers and dependencies

Step 050 adds owner-scoped task dependencies through `todo_dependencies`. A
dependency means the current task is waiting on another private task before it
is unblocked.

- `TodoDependency` stores `user_id`, `todo_id` (the waiting task), and
  `depends_on_todo_id` (the blocker). A unique index prevents duplicate edges
  for the same user/task/blocker combination.
- `TodoDependencyQuery` is the single read and validation boundary. It lists
  dependencies for a task, lists candidate blockers, finds rows for removal,
  computes the blocked smart view, and prevents self-references, duplicate
  edges, foreign tasks, inactive blockers, and cycles.
- `AcyclicTodoDependency` gives Livewire field-level feedback for dependency
  picker validation, while `AddTodoDependency` repeats the owner, active-state,
  duplicate, and cycle checks at the action boundary.
- The task detail page remains a normal class-based Livewire component. It
  shows open/resolved blocker badges, a translated blocked callout, a private
  blocker picker, removal buttons, and a "This task blocks" summary.
- A task's blocked state is derived from open dependency rows whose blocker task
  is not completed. Completing the blocker resolves the waiting task immediately
  on the next read; no background job or scheduled unblock process is needed.
- `/todos/blocked` is a protected class-based Livewire smart view for active
  owner tasks with open blockers. The main task list also has a `due=blocked`
  filter, blocked summary count, and blocked badges.
- Archived waiting tasks are hidden from blocked views because they are not
  active. Archived blocker tasks still count as unresolved until completed or
  removed from the dependency list, which keeps the relationship explicit.
- Dependency management is synchronous and web-triggered. It requires no cron,
  queue worker, supervisor, shell, Artisan command, paid service, chunk
  processor, retry loop, or background unblock process during normal hosted
  usage.

## Cleanup smart views

Step 051 adds `/todos/cleanup`, a protected class-based Livewire/Flux page for
reviewing owner-scoped active tasks that need planning attention. It does not add
storage; the page is derived from existing task, tag, project, and dependency
rows.

- `TodoCleanupFilters` stores allow-listed URL state for `view`, `search`,
  `sort`, and `direction`.
- `TodoCleanupQuery` is the read boundary for cleanup lists and summary counts.
  It starts from `Todo::ownedBy($user)->active()`, then applies one cleanup view:
  stale, unplanned, blocked, or risky.
- Stale tasks are active tasks untouched for 14 days. Unplanned tasks have no
  project, no due date, no owner tags, and are not still in the quick-capture
  Inbox. Blocked tasks have at least one open owner-scoped blocker. Risky tasks
  are urgent undated/due work, overdue high-priority work, or due blocked work.
- Invalid cleanup view URL state fails closed to an empty result instead of
  widening the query. Invalid sort and direction values fall back to safe
  ordering and render translated unavailable chips.
- The page includes a debounced search input, Flux filter panel controls, active
  chips, Flux pagination, translated empty states, and task/project links.
- Cleanup is read-only and synchronous. It requires no cron, queue worker,
  supervisor, shell, Artisan command, paid service, chunk processor, retry loop,
  or background cleanup job during normal hosted usage.

## Automation rules

Step 052 adds `/todos/automations`, a protected class-based Livewire/Flux page
for browser-triggered task automation. Rules and run logs are private rows owned
by the current user.

- `AutomationRuleQuery` is the read boundary for listing and resolving rules.
  It starts from `AutomationRule::ownedBy($user)` and eager-loads the latest run
  report.
- `CreateAutomationRule`, `ToggleAutomationRule`, and `RunAutomationRule` are
  the write boundaries. The Livewire component validates form state, resolves
  submitted rule ids through the owner-scoped query, authorizes the rule, and
  delegates all mutations to the actions.
- Built-in rules are `promote_overdue_tasks`, which raises active overdue low
  or normal priority tasks to High, and `archive_completed_tasks`, which
  archives completed tasks older than seven days.
- Runs process a bounded owner-scoped chunk through
  `App\Actions\Processing\RunManualWebProcess`. Matched, changed, and remaining
  counts are stored in `automation_rule_runs` so users can test, retry, or run
  again to resume remaining work.
- Disabled rules record a disabled run and change nothing. Dry runs record the
  current match count without mutating tasks.
- The workflow uses no cron, queue worker, supervisor, shell, Artisan command,
  terminal dependency, paid service, or hosted automation provider during normal
  usage.

## Inbox

Step 044 adds a dedicated `todos.inbox` Livewire page for fast, unsorted task
capture and later triage.

- Captured tasks are normal private `todos` rows with an
  `inbox_captured_at` timestamp. They are not inferred from "No project", so a
  legitimate no-project task is not automatically treated as inbox work.
- Inbox reads use `TodoInboxQuery`, which scopes to the current user, active
  tasks only, and `inbox_captured_at is not null`. Completed, archived, trashed,
  triaged, and foreign tasks are excluded.
- Quick capture uses `CaptureInboxTodo`, which creates a normal task through
  `CreateTodo`, assigns the timestamp, and defaults to Normal priority with no
  project or due date.
- Triage uses `TriageInboxTodo`, which delegates organization updates to
  `UpdateTodo` and clears `inbox_captured_at` only after validation and
  authorization pass.
- Captured titles are normalized with `InboxCaptureTitle`, limited to 120
  characters, and rejected if they contain no visible text. Both the Livewire
  form and action layer enforce this.
- The workflow is single-task and synchronous. It needs no cron, queue worker,
  supervisor, shell, Artisan command, paid service, retry loop, resume token, or
  chunk processor during normal hosted usage.
- Demo seeding gives every local/testing/demo user two inbox tasks so
  `https://ruflo.test/todos/inbox` has immediate data after seeding.

## Calendar view

Step 041 adds `todos.calendar`, a protected class-based Livewire page for a
month-style view of the same private task data. The calendar is self-hosted and
does not require cron, queues, workers, terminal commands, or external calendar
services.

Calendar reads use `TodoCalendarQuery`, which scopes to the current user,
eager-loads current-user project and tag relations, and shows active tasks with
due dates inside the selected month. Completed, archived, trashed, and foreign
tasks are excluded from the month grid. Active tasks without a due date appear
in a separate unscheduled section so "No due date" is still visible without
mixing it into a date cell.

Month navigation is URL-backed through the `month=YYYY-MM` query parameter and
validated by `CalendarMonth`. Invalid month input cannot widen a query; bad
query strings reset to the current month and show a translated notice. Date
comparisons continue to use the app timezone documented above.

Reminder and recurring-task panels are present as translated, restricted-hosting
safe placeholders. The current reminder table has no owner or schedule columns,
and recurrence models do not exist yet, so the calendar does not query or fake
those domains. The dedicated reminder and recurrence steps must add owned
schedule/rule data before those lanes render real entries.

## Kanban board

Step 040 adds `todos.board`, a protected class-based Livewire page for the
same private task data. The board has Active, Completed, and Archived columns.
Trash is intentionally excluded because restoring deleted work already has a
separate recovery flow.

Board reads use `TodoBoardQuery`, which scopes to the current user, eager-loads
current-user project/tag badges, and limits each column to 25 cards. Column
counts come from the same owner-scoped summary used by the list.

Board movement uses `MoveTodoOnBoard` and the existing lifecycle actions:

- moving to Completed completes active tasks and unarchives archived tasks
  before completing when needed;
- moving to Active reopens completed tasks and unarchives archived tasks before
  reopening when needed;
- moving to Archived archives active or completed tasks while preserving
  completion state;
- moving projects accepts only the current user's active projects or "No
  project";
- invalid columns are rejected by `BoardStatus`, and invalid project targets
  reuse `OwnedActiveProject`.

The board uses reliable fallback buttons for status movement instead of
drag/drop. There is no position column yet, so drag ordering would create a
second, unpersisted ordering system. When manual ordering is added later, it
should introduce a per-user position field and tests before enabling drag/drop
ordering.

## Bulk actions

`BulkCompleteTodos`, `BulkArchiveTodos`, `BulkUnarchiveTodos`, `BulkMoveTodos`,
`BulkDeleteTodos`, and `BulkRestoreDeletedTodos` each take the user plus a list of selected ids and
**re-scope the selection to the user's own tasks inside the query**
(`$user->todos()->…->whereKey($ids)`). Consequences:

- Livewire selection validates every submitted id with `OwnedTodo`, so foreign
  ids are rejected before mutation and the current user's selected rows remain
  unchanged.
- The action layer still re-scopes direct action calls. Foreign, missing, or
  non-actionable ids are skipped there and reported through `BulkActionResult`
  as selected/affected/skipped/failed counts.
- Bulk complete only affects **active** tasks; bulk archive only **non-archived**
  tasks — meaningless transitions are no-ops, not errors.
- Bulk unarchive only affects archived tasks and preserves completion state.
- Bulk move updates only owned tasks and only accepts an owned, active target
  project; an empty target moves tasks back to "No project".
- Bulk delete is soft (recoverable), confirmed in the UI, and delegates to the
  eventful single-task delete action.
- Bulk restore from Trash only affects owned deleted tasks and preserves
  completion/archive state.
- Step 039 adds a select-visible control for the current page, a clear-selection
  control, a translated result callout/toast, and a Flux confirmation modal for
  bulk delete. Bulk actions are synchronous and bounded to the visible selected
  ids; they require no queue, cron, worker, terminal, or Artisan command during
  normal usage.

## Templates

Step 043 adds reusable task templates at `todos.templates`.

- Templates are stored in `todo_templates` with `user_id`, per-user unique
  `name`, `kind` (`task`, `project`, `checklist`, `routine`), `visibility`,
  generated task title, priority, due offset, optional project name, optional
  description, and checklist item titles.
- `TaskTemplateKind` owns labels, colors, and icons for Flux badges.
- Template reads flow through `TodoTemplateListQuery`, and every submitted
  template id is resolved through `findFor($user, $id)` before mutation.
- Template creation and edits use `TodoTemplateData`, `TemplateName`, and
  `TemplateChecklistItems`. Action classes repeat backend validation and
  per-user duplicate-name checks.
- Instantiation creates a real task through `CreateTodo`, resolves or creates
  an owner-scoped active project by `project_name`, and creates contained
  checklist rows through `CreateTodoChecklistItem`.
- Template deletion removes only the reusable template; tasks already created
  from it stay intact.
- Shared visibility is stored and rendered, but no collaboration access is
  granted yet because workspace memberships and roles do not exist.
- Template workflows are synchronous Livewire actions. They require no cron,
  queue worker, supervisor, terminal, or Artisan command during normal usage.

## Goals and milestones

Step 046 adds private goals and milestones as real, owner-scoped records:

- `goals` are owned by one user and can optionally point at one active project.
- `goal_milestones` are owned by the same user and belong to one goal.
- `todos.goal_id` and `todos.goal_milestone_id` link existing tasks to a whole
  goal or to a specific milestone. If a goal or milestone is removed later, the
  task link nulls instead of deleting the task.
- `GoalListQuery` is the read boundary for the Goals page. It eager-loads the
  current user's project, milestones, linked tasks, and milestone tasks without
  hydrating foreign labels.
- `GoalProgress` calculates progress from real units only: completed linked
  tasks plus checked-in milestones divided by total linked tasks plus total
  milestones. Goals with no units show 0% instead of fake progress.
- `CreateGoal`, `CreateGoalMilestone`, `CheckInGoalMilestone`, and
  `LinkTodoToGoal` repeat policy checks and owner scoping at the action layer.
- Milestone "check in" is a synchronous Livewire action that toggles
  `completed_at`; full habit streak tracking lives in the separate Step 047
  habits tracker.
- The workflow is web-only and bounded. It requires no cron, queue worker,
  supervisor, terminal access, Artisan command, external service, paid feature,
  chunk processor, retry loop, or resume state during normal usage.

## Habits tracker

Step 047 adds private habits and habit check-ins as real, owner-scoped records:

- `habits` are owned by one user, can optionally support one active goal, and
  store a `daily` or `weekly` frequency plus a period target count.
- `habit_check_ins` are owned by the same user and belong to one habit. A unique
  `(habit_id, occurred_on)` constraint prevents duplicate check-ins for the
  same habit day.
- `todos.habit_id` links existing tasks to one habit. The task is preserved and
  the link nulls if a habit is removed later.
- `HabitListQuery` is the read boundary for `/habits`. It eager-loads current
  user goals, check-ins, and linked tasks while constraining every relation to
  the same owner.
- `HabitProgress` calculates current-period progress and current/best streaks
  from actual check-in dates only. Daily progress counts today's check-in;
  weekly progress counts unique check-in days in the current week against the
  stored target. Empty habits show 0% and zero streaks.
- The check-in UI only toggles today's check-in. It does not backfill arbitrary
  dates from the browser, which keeps spoofing and fake historical streaks out
  of the first habit implementation.
- `CreateHabit`, `ToggleHabitCheckIn`, and `LinkTodoToHabit` repeat policy
  checks and owner scoping at the action layer.
- The workflow is web-only and bounded. It requires no cron, queue worker,
  supervisor, terminal access, Artisan command, external service, paid feature,
  chunk processor, retry loop, or resume state during normal usage.

## UI

- Reusable `x-ui.stat` (summary counters) and `x-ui.status-badge` components;
  the lifecycle segmented control (Flux Free has no `tabs`) now covers Active,
  Completed, Archived, and Trash.
- The create form renders validation feedback beside the Flux title, priority,
  due date, project, and tag controls so failed input stays visible and
  recoverable.
- The edit modal uses the same validation boundary and renders errors beside
  every editable field; invalid priority, due date, project, or tag input keeps
  the modal open and leaves the task unchanged.
- Per-row badges: priority (hidden when Normal to reduce noise), due date
  (red overdue / amber today / zinc upcoming), project, and tags.
- Task titles link to `todos.show`, a private detail page that reuses the same
  status, priority, due-date, project, and tag badges after resolving the task
  through the owner-scoped query boundary. Trash rows do not link to detail
  pages until restored.
- A filter toolbar (search, project, tag, priority, due, sort, direction,
  reset), a saved-views strip for saving/applying/deleting named view criteria,
  a bulk selection row, a bulk toolbar that appears on selection, a Flux bulk
  delete confirmation modal, Kanban, cleanup, and automation shortcuts, and a
  "Manage" modal for
  creating/renaming/archiving/restoring/deleting projects and creating/deleting
  tags, plus a Templates shortcut for reusable task setups.
- The templates page renders a Flux create form, radio-card template type
  controls, status/priority/due/project/checklist previews, per-template quick
  actions, an edit modal, field-level errors, and a translated empty state.
- The goals page renders Flux goal cards, translated create/add/link forms,
  progress bars with text alternatives, milestone check-in buttons, task-link
  controls, and empty states.
- The habits page renders Flux habit cards, translated create/link forms,
  progress bars with text alternatives, today check-in buttons, current/best
  streak counters, task-link controls, and empty states.
- Project badges in task lists and task detail pages link to the private
  project detail page. The detail page renders the project status, scoped
  lifecycle counts, a paginated task list, and a translated empty state.
- Tag badges in task lists, task detail pages, and project detail pages link to
  the existing tag filter with `wire:navigate`; foreign tag ids still resolve
  to an empty owner-scoped result if a URL is tampered.
- All text is translatable via `lang/en/todos.php`. Project/tag pickers only
  ever list the current user's own resources.

## Performance

- `TodoListQuery::filtered()` eager-loads `project` and `tags` to avoid N+1
  when rendering badges.
- `TodoListQuery::forProjectDetail()` and `projectSummaryFor()` keep project
  detail reads and counts owner-scoped and paginated.
- `TodoBoardQuery` keeps board columns owner-scoped, eager-loaded, and capped
  at 25 cards per column.
- The summary (active/completed/archived/trash/overdue) is one aggregate query.
- Composite indexes back the common filters: `(user_id, project_id)`,
  `(user_id, due_date)`, `(user_id, priority)`, `(user_id, archived_at)`,
  `(user_id, is_completed)`.
- Saved views are loaded through `SavedTodoViewListQuery` by current user and
  ordered by name/id; `(user_id, name)` prevents duplicate names per user and
  `(user_id, updated_at)` supports owner-scoped listing.
- Templates are loaded through `TodoTemplateListQuery` by current user and
  ordered by updated timestamp/name. `(user_id, name)` prevents duplicate
  names, while `(user_id, kind)` and `(user_id, visibility)` support owner-scoped
  template listing and future filters.
- Habits are loaded through `HabitListQuery` by current user and ordered by
  title. `(user_id, archived_at)`, `(user_id, frequency)`, `(user_id, goal_id)`,
  `(user_id, occurred_on)`, `(user_id, habit_id)`, and `(habit_id, occurred_on)`
  support owner-scoped habit cards, progress calculations, and check-in
  uniqueness.
- Inbox reads are backed by `(user_id, inbox_captured_at)` and stay paginated
  through `TodoInboxQuery`.
- Focus reads reuse existing task indexes for owner, priority, and due-date
  ordering. The derived set is bounded to the normal target size plus any
  additional urgent tasks, and all mutations re-resolve through
  `TodoFocusQuery`.
- Goals use `(user_id, archived_at)`, `(user_id, target_date)`,
  `(user_id, goal_id, position)`, `(user_id, goal_id)`, and
  `(user_id, goal_milestone_id)` indexes for owner-scoped listing, progress,
  and task linking.
- Automation rules use `(user_id, name)` for per-user uniqueness,
  `(user_id, is_enabled, kind)` for owner-scoped rule filtering, and run-log
  indexes on `(user_id, status)` plus `(automation_rule_id, created_at)` for
  recent report lookups.

## Intentionally not implemented

Manual drag ordering, sub-projects, tag colors editing UI, automatic recurring
tasks, reminders, collaboration, and generic cross-feature web processing.
Manual ordering, when added, should store a per-user position and only apply
when no sort/filter overrides it.

## Later steps

Reminders and notifications: separating "when it's due" (due_date) from "when
to be reminded", overdue alerts, today/upcoming notifications, a daily summary,
notification preferences, and safe queue/scheduler behavior — all reusing the
date buckets and owner scoping established here.
