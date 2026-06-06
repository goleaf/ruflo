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
