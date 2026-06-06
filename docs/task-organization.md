# Task Organization

Step 4 turns the private task lifecycle into a usable productivity system:
projects, tags, priorities, due dates, search, filters, sorting, and bulk
actions. Everything here is owner-scoped on top of the model in
[`authorization.md`](authorization.md) and the lifecycle in
[`task-lifecycle.md`](task-lifecycle.md).

## What was added

| Concept | Storage | Ownership |
| --- | --- | --- |
| **Project** (a.k.a. list) | `projects` table; `todos.project_id` (nullable FK) | `projects.user_id`, private |
| **Tag** | `tags` table; `tag_todo` pivot | `tags.user_id`, private, unique name per user |
| **Priority** | `todos.priority` (enum string) | n/a — `App\Enums\Priority` (low/normal/high/urgent) |
| **Due date** | `todos.due_date` (date) | n/a |

## Projects

- A task belongs to at most one project. A task with no project is valid and
  filterable ("No project").
- Projects can be **renamed**, **archived** (hidden from active pickers/filters,
  reversible), **restored**, or **deleted**. Deleting a project **never deletes its tasks** — the
  `project_id` FK is `nullOnDelete`, so the tasks fall back to "No project".
- `project_id` is **not** mass-assignable. `CreateTodo`/`UpdateTodo` set it
  directly only after re-scoping it to the user (`ResolvesTodoOrganization`),
  so a forged request can never attach a task to another user's project.
- Authorization: `ProjectPolicy` (owner-only `view`/`update`/`archive`/
  `restore`/`delete`; denials read as not-found).

## Tags

- Many-to-many with tasks. Tag names are normalized (squished + lower-cased) so
  "Work", "work", and " work " collapse to one label; a per-user unique
  constraint enforces it. Two different users may each own a tag named "work".
- Creating a tag uses `firstOrCreate`, so the same name never fragments.
- Deleting a tag removes the pivot rows (cascade) but never the tasks.
- Tag ids on a task are re-scoped to the user before syncing — foreign tag ids
  are silently dropped.
- Authorization: `TagPolicy` (owner-only; not-found denials).

## Priority

`App\Enums\Priority`: Low / Normal / High / Urgent, each with a translatable
label, a badge color, and a sort `weight()`. Stored as the enum's string value
and cast on the model. Validated against `Priority::values()` on input.

## Due dates & date buckets

Dates are compared in the **application timezone** (server) until per-user
timezone arrives in a later step — documented here so the assumption is
explicit. The buckets, all **active-only** (completed/archived tasks are never
overdue/today/upcoming):

- **Due today** — `due_date == today`, active.
- **Overdue** — `due_date < today`, active. Completing or archiving a task
  stops it from being overdue.
- **Upcoming** — `due_date > today`, active.

These live as model scopes (`scopeDueToday`/`scopeOverdue`/`scopeUpcoming`) plus
`isOverdue()`/`isDueToday()` helpers, and feed the summary's `overdue` counter.

## Search, filters, sorting

All of this is centralized in `App\Queries\Todos\TodoListQuery::filtered()`,
which takes a validated `TodoFilters` value object and always starts from the
owner scope. No filtering happens in the view or the component beyond building
the (sanitized) filter object.

- **Search** — case-insensitive `LIKE` on title, with LIKE wildcards (`%`, `_`)
  escaped via an `ESCAPE` clause so a search for `50%` matches the literal text
  instead of returning everything.
- **Filters** — lifecycle tab (active/completed/archived), project (or "none"),
  tag, priority, and due bucket. Every filter value is sanitized in the
  component's `buildFilters()` before it reaches the query: unknown enum/sort/
  due values fall back to safe defaults and can never widen scope.
  Numeric project/tag filters are re-checked inside `TodoListQuery::filtered()`;
  foreign, archived, or missing ids return an empty result rather than applying
  another user's id or falling back to an unfiltered list.
- **Sorting** — `created`, `updated`, `due` (nulls last), `priority` (by weight
  via a bounded `CASE`), `project` (by owned project name, ungrouped tasks last),
  or `title`, each asc/desc. The sort key is validated against
  an allow-list, so a tampered `?sort=` string can never inject SQL — it falls
  back to `created`. (Tested with an injection-style value.)
- **Pagination** — the list is paginated (15/page) via `WithPagination`; it
  never loads an unbounded result set.

Filters, search, sort, and pagination compose: changing any of them resets the
page and clears the bulk selection.

## Bulk actions

`BulkCompleteTodos`, `BulkArchiveTodos`, `BulkUnarchiveTodos`, `BulkMoveTodos`,
and `BulkDeleteTodos` each take the user plus a list of selected ids and
**re-scope the selection to the user's own tasks inside the query**
(`$user->todos()->…->whereKey($ids)`). Consequences:

- A foreign id in the payload is silently excluded — a bulk action can never
  touch another user's task. (Tested by mixing an intruder's id into the
  selection.)
- Bulk complete only affects **active** tasks; bulk archive only **non-archived**
  tasks — meaningless transitions are no-ops, not errors.
- Bulk unarchive only affects archived tasks and preserves completion state.
- Bulk move updates only owned tasks and only accepts an owned, active target
  project; an empty target moves tasks back to "No project".
- Bulk delete is soft (recoverable) and confirmed in the UI.

## UI

- Reusable `x-ui.stat` (summary counters) and `x-ui.status-badge` components;
  the lifecycle segmented control (Flux Free has no `tabs`).
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
  through the owner-scoped query boundary.
- A filter toolbar (search, project, tag, priority, due, sort, direction,
  reset), a bulk toolbar that appears on selection, and a "Manage" modal for
  creating/renaming/archiving/restoring/deleting projects and creating/deleting
  tags.
- All text is translatable via `lang/en/todos.php`. Project/tag pickers only
  ever list the current user's own resources.

## Performance

- `TodoListQuery::filtered()` eager-loads `project` and `tags` to avoid N+1
  when rendering badges.
- The summary (active/completed/archived/overdue) is one aggregate query.
- Composite indexes back the common filters: `(user_id, project_id)`,
  `(user_id, due_date)`, `(user_id, priority)`, `(user_id, archived_at)`,
  `(user_id, is_completed)`.

## Intentionally not implemented

Manual drag ordering, saved/named filter views, sub-projects, project-level
detail pages, tag colors editing UI, recurring tasks, reminders, dashboard,
collaboration. Manual ordering, when added, should store a per-user position
and only apply when no sort/filter overrides it.

## What Step 5 builds next

Reminders and notifications: separating "when it's due" (due_date) from "when
to be reminded", overdue alerts, today/upcoming notifications, a daily summary,
notification preferences, and safe queue/scheduler behavior — all reusing the
date buckets and owner scoping established here.
