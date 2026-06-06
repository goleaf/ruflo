# Reports

## Step 065 Charts Without Paid Services

Reports trend charts now render through the shared
`resources/views/components/ui/local-bar-chart.blade.php` component. The
component uses local Blade, Flux text/heading elements, Tailwind CSS utility
classes, inline width percentages from owner-scoped query data, and
screen-reader-only row summaries. It does not load a chart JavaScript package,
CDN asset, hosted charting service, paid analytics service, or Flux Pro chart
runtime.

Each reports chart keeps `role="img"`, an owner-specific translated ARIA label,
`data-chart-driver="local-css"`, and `data-chart-library="none"` so tests and
future audits can distinguish the local fallback from external charting
libraries.

## Step 064 Reports Overview

The app now includes a protected reports overview at `/reports`. It summarizes
private productivity, habit, project, time, and overdue signals for the
authenticated user without external reporting services or background jobs.

## Data Boundary

`App\Queries\Reports\ReportsOverviewQuery` is the read boundary for reports.
It calculates:

- active tasks, completed-this-week tasks, previous-week completions, due-today
  tasks, next-seven-day tasks, Inbox tasks, and completion percentage,
- current overdue task totals, urgent/high overdue counts, overdue age buckets,
  and the oldest overdue age,
- active habits, today check-ins, this-week and previous-week check-ins,
  distinct checked habits, and adherence percentage,
- active project counts, projects with active tasks, completed project tasks,
  overdue project tasks, no-project active tasks, and top active project cards,
- completed time today, this week, previous week, weekly delta, and active
  timers.

The Livewire page does not query models directly. It receives the owner-scoped
report array from the query, formats values, and renders translated Flux UI.

## UI Contract

The reports page uses Flux cards, badges, buttons, progress bars, and callouts.
It includes:

- responsive summary widgets for productivity, habits, projects, time, and
  overdue work,
- browser-rendered accessible bar summaries for productivity, overdue age,
  habit momentum, and tracked time,
- project highlight cards with owner-scoped task filters,
- an empty state when there is no report activity,
- session-backed compact/details and show/hide-trends settings.

## Privacy

Reports are scoped to the authenticated owner before rendering. Foreign,
archived, deleted, and unauthorized task data is excluded from counters and
project cards. Archived projects are not shown in project highlights, and
foreign project or task names never reach the page.

## Restricted Hosting

Reports are calculated during normal authenticated web requests. They require
no cron, queue worker, supervisor, terminal access, Artisan command, paid API,
hosted analytics, hosted charting service, or background job. Refreshing the
reports page is the web trigger that recalculates current report values.

## Demo Data

Existing demo seeders already create tasks, projects, habits, check-ins, and
time entries. No new model, migration, factory, or seeder was needed for this
step.
