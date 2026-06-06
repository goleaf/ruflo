# Seeding Strategy

Step 012 covers the committed model set:

- `User`
- `Goal`
- `GoalMilestone`
- `Habit`
- `HabitCheckIn`
- `PomodoroSession`
- `Project`
- `SavedTodoView`
- `Tag`
- `TimeEntry`
- `Todo`
- `TodoChecklistItem`
- `TodoDependency`
- `TodoTemplate`

The tracked `Reminder` model is currently a placeholder with no ownership, schedule, lifecycle, or message columns, so it is not seeded yet. Seeder coverage asserts the placeholder table stays empty until the reminder domain exists. Future models for recurrence, comments, attachments, activity, invites, settings, and collaboration are not seeded yet because those committed models do not exist yet.

## Seeders

`DatabaseSeeder` calls seeders in this order:

1. `DemoUserSeeder`
2. `TodoSeeder`

`DemoUserSeeder` creates the configured demo users only when the app is running in a safe environment: `local`, `testing`, or `demo`, and when the demo login panel is enabled. The first configured demo user is seeded as an admin for protected local maintenance access; the second configured demo user is seeded as a normal account for denial and isolation checks.

`TodoSeeder` creates a private workspace for every existing user:

- active `Work` and `Home` projects,
- archived `Old plans` project,
- `urgent` and `waiting` tags,
- active, due-today, overdue, upcoming, completed, archived,
  archived-completed, and trashed tasks.
- two quick-capture inbox tasks per user, backed by `todos.inbox_captured_at`,
  so `/todos/inbox` is useful immediately after seeding.
- two cleanup demo tasks per user: `Refresh the old cleanup note` for stale
  review and `Choose a home admin next step` for unplanned review.
- contained checklist rows on due-today, overdue, upcoming, and archived tasks
  so task detail pages show progress and locked archived checklist behavior.
- three saved views per user: `Today focus`, `Urgent work`, and
  `Waiting on others`.
- three reusable templates per user: `Daily planning routine`,
  `Project kickoff`, and `Bug triage checklist`.
- two goals per user: `Launch the personal command center` and
  `Plan a calmer weekend`.
- three milestones per user, with one checked in, linked to existing seeded
  tasks so `/goals` shows real progress immediately.
- two habits per user: `Plan the day` and `Run the weekly review`.
- six real habit check-ins per user, linked to seeded habits so `/habits` shows
  current progress and streaks immediately.
- one paused Pomodoro focus session per user, linked to `Review the current
  flow`, so `/todos/focus` can demonstrate timer resume immediately after
  seeding.
- two completed time entries per user: one task-linked entry for `Review the
  current flow` and one project-only `Work` entry, so `/todos/time` shows task
  and project totals immediately after seeding.
- one task dependency per user: `Send the overdue report` waits on `Review the
  current flow`, so `/todos/blocked` and the main `due=blocked` filter have
  immediate demo data.

Step 041's calendar view reuses that catalog: the seeded due-today, overdue,
upcoming, and no-due-date tasks give the local `/todos/calendar` page immediate
month and unscheduled examples without adding new model rows or changing the
stable seeder counts. Reminder and recurrence rows remain deferred until those
schemas exist.

Step 045's focus mode also reuses the current catalog. `Review the current
flow` is high priority and due today, while `Send the overdue report` is urgent
and overdue, so `/todos/focus` has realistic current work immediately after
seeding without adding another table or a focus-specific seed model.

Step 046's goals page links existing seeded tasks into seeded goals and
milestones. It does not create extra task rows for fake progress; progress comes
from real task completion and milestone check-ins.

Step 047's habits page links existing seeded tasks into seeded habits and
creates real check-in rows for today and recent periods. It does not create
fake streak counters; progress and streaks are derived from `habit_check_ins`.

Step 048's Pomodoro focus timer links a paused session to the existing
`Review the current flow` focus task for each seeded user. It does not add fake
time tracking totals; the row exists only to demonstrate browser-triggered
timer resume and state transitions on `/todos/focus`.

Step 049's time tracking adds real completed `time_entries` rows for each
seeded user. The rows are not active timers, do not require background work, and
exist only as demo/history data for `/todos/time`.

Step 050's dependencies add one real `todo_dependencies` edge per seeded user.
The edge is private, owner-scoped, and exists only as demo/planning data for
`/todos/blocked` and task detail pages.

Step 051's cleanup views add two normal task rows per seeded user. The stale
cleanup task is organized under `Work` and has an intentionally old `updated_at`
timestamp reapplied on every seed run. The unplanned cleanup task has no project,
due date, tag, or inbox timestamp, so `/todos/cleanup?view=unplanned` has
immediate demo data without confusing it with the quick-capture Inbox.

## Idempotency

Seeders are idempotent for the current demo catalog. Re-running them updates existing demo records instead of creating duplicate users, tags, projects, saved views, or seeded task titles.

Checklist rows are upserted per seeded task/title and keep positions stable when
the seeder is run again.

Templates are upserted per user/name and keep private data isolated. The
`Project kickoff` template is marked shared so the UI can show the visibility
state, but it is still owner-only until the collaboration/member steps add real
role rules.

Inbox demo tasks are upserted per user/title and keep their captured timestamp
fresh on reseed. They remain normal owner-scoped todos and do not grant any
global or shared access.

Goals and milestones are upserted per user/title and per goal/title. Task links
are refreshed on reseed so the local demo remains useful after prior manual
testing without duplicating goal rows.

Habits are upserted per user/title and check-ins are upserted per habit/date.
Task links are refreshed on reseed so the local demo remains useful after prior
manual testing without duplicating habit rows or check-in dates.

Pomodoro sessions are upserted per user, task, and active status. Re-running
the seeder refreshes the paused demo timer without creating duplicate active
focus sessions.

Time entries are upserted per user/task or user/project, source, and tracked
date. Re-running the seeder refreshes demo durations/notes without creating
duplicate time history rows.

Task dependencies are upserted per user, waiting task, and blocker task.
Re-running the seeder refreshes the same blocker relationship without creating
duplicate dependency edges.

Placeholder reminder rows are intentionally excluded from the current catalog because they would not be owned by a user or connected to a task.

## Production Safety

Known demo users are not created outside safe environments. A production deployment should use:

```text
APP_ENV=production
RUFLO_DEMO_LOGIN_PANEL=false
```

The normal application must not depend on Artisan or seed commands during everyday hosted usage. Seeding is setup/demo data only.
