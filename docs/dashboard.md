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
