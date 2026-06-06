# Dashboard

## Step 056 Daily Summary Dashboard

The dashboard now includes a browser-rendered daily summary card above the
existing workspace counters. It replaces scheduled summary delivery with live
private counters that load when the authenticated user opens `/dashboard`.

## Data Boundary

`App\Queries\Dashboard\DailyDashboardQuery` is the read boundary for the daily
card. It reads:

- owner-scoped active task date buckets for due today, overdue, next seven days,
  unplanned tasks, active total, scheduled total, and schedule coverage,
- owner-scoped blocked task counts through `TodoListQuery::blockedFor($user)`,
- owner-scoped reminder counts for due and pending reminders,
- owner-scoped completed time-entry totals for today and active timer counts,
- unread database notification counts through `NotificationInboxQuery`.

The Livewire dashboard component does not query task, reminder, time-entry, or
notification models directly. All counters are scoped to the authenticated
user before rendering. Completed, archived, deleted, and foreign tasks are
excluded from actionable daily task counters.

## UI Contract

The daily summary uses Flux cards, callouts, badges, progress, and buttons. It
has:

- an empty state when no daily work exists,
- an attention state when due work, blocked work, due reminders, or unread
  notifications exist,
- a planned state when upcoming, unplanned, pending reminder, time, or timer
  data exists without urgent attention,
- a responsive two-column to four-column counter grid,
- an accessible schedule-coverage progress summary,
- a compact/details widget setting stored as a Livewire session property,
- keyboard-safe Flux shortcut links to Today, Overdue, Blocked, Reminders,
  Notifications, and Time tracking.

## Restricted Hosting

The daily summary is computed during normal authenticated web requests. It
requires no cron, queue worker, supervisor, terminal access, Artisan command,
email provider, push provider, paid API, or hosted service. Due reminders are
still processed through the existing web-triggered reminder flow on dashboard
open and reminder-page actions.

There is no background daily-summary job. Refreshing the dashboard or navigating
back to it is the web trigger that recalculates the summary for the current
user.

## Demo Data

Existing demo seeders already create due-today tasks, overdue tasks, blocked
tasks, due reminders, unread notifications, and same-day time entries, so the
daily summary card has realistic local data at `https://ruflo.test/`.

## Step 061 Dashboard Foundation

The dashboard now adds a foundation widget grid for the major private work
systems: Today, Overdue, Upcoming, Priorities, Reminders, Recurring, Goals,
Habits, Projects, and Time. The widgets sit above the older summary counters
and link to the protected owner-scoped pages that already handle each workflow.

`App\Queries\Dashboard\DashboardFoundationQuery` is the read boundary for these
widgets. It aggregates active task date buckets and priorities, due/pending
reminders, enabled/paused/generated recurrence state, open goals and
milestones, active habits and check-ins, active projects, project task coverage,
completed time, weekly time, and active timers only after scoping to the
authenticated user.

The Livewire dashboard stores a compact/details widget preference in session,
renders Flux cards and badges in a responsive grid, and includes a
browser-rendered comparison chart with screen-reader summaries. No charting
service, scheduler, queue worker, cron entry, terminal action, Artisan command,
or paid API is required for normal use.

No new model, factory, or seeder was needed for this step. Existing todo,
reminder, recurrence, goal, habit, project, and time seed data already exercises
the dashboard widgets locally.

## Step 062 Dashboard Customization

Users can now customize the foundation widget grid without adding database
state. The dashboard stores two small Livewire session arrays: widget order and
hidden widget keys. The values are normalized against the server-owned widget
allow-list on mount and on every Livewire action, so stale or tampered keys do
not widen dashboard data or trigger arbitrary links.

The Flux settings panel lets users:

- show or hide each foundation widget,
- move widgets up or down in the render order,
- reset the widget layout back to the default order and visibility,
- hide every foundation widget and see an empty state with a reset action.

The same owner-scoped `DashboardFoundationQuery` remains the read boundary for
all counters. Customization changes only presentation order and visibility; it
does not change which private records are counted. The browser-rendered chart
uses the visible widget order and disappears when all widgets are hidden.

No Form Request or custom validation rule was added because the customization
input is not a request-driven controller action and the business rule is local
to the fixed dashboard widget key list. Livewire actions validate widget keys
and move directions on the server before changing session-backed preferences,
and the validation messages are translated in `lang/en/dashboard.php`.

No model, migration, factory, seeder, queue, cron job, worker, supervisor,
Artisan command, paid API, or hosted charting service is required for this
step. Existing dashboard seed data continues to show realistic widget states at
`https://ruflo.test/dashboard`.

## Step 063 Project Progress Dashboard

The dashboard now includes a project and list health section between the
foundation widgets and the older summary counters. It shows active owner
projects with completion percentage, active and completed task counts, overdue
tasks, next-seven-day tasks, undated tasks, stale tasks, and attention signals.
Active tasks without a project are shown in a separate no-project panel so
users can organize loose work without losing privacy boundaries.

`App\Queries\Dashboard\ProjectProgressDashboardQuery` is the read boundary for
this section. It reads only active projects owned by the authenticated user,
counts only tasks owned by the same user, excludes soft-deleted tasks, and
excludes tasks attached to archived projects from project totals. The dashboard
view never receives foreign project names, archived project names, or foreign
task titles.

The UI uses Flux card, badge, progress, callout, and button components with
translated labels, empty states, action labels, and screen-reader summaries. It
links each project card to the protected project detail page and the owner
scoped task-list filter, while no-project work links to the task list's
`project=none` filter.

Project progress recalculates when the authenticated dashboard renders. It
requires no cron, queue worker, supervisor, terminal access, Artisan command,
paid charting service, hosted API, email provider, or background job. No model,
migration, factory, or seeder was added because existing project and task
models already contain the state needed for this browser-triggered summary.
