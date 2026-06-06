# Factory Coverage

Step 011 covers the tracked application models:

- `App\Models\User`
- `App\Models\Goal`
- `App\Models\GoalMilestone`
- `App\Models\Habit`
- `App\Models\HabitCheckIn`
- `App\Models\PomodoroSession`
- `App\Models\Project`
- `App\Models\Reminder`
- `App\Models\SavedTodoView`
- `App\Models\Tag`
- `App\Models\TimeEntry`
- `App\Models\Todo`
- `App\Models\TodoChecklistItem`
- `App\Models\TodoTemplate`

`App\Models\Reminder` is currently a placeholder model with only an `id` and timestamps. Its default factory creates a valid record, but named reminder states are intentionally deferred until the reminder feature steps add owned reminder fields, lifecycle columns, and processing status.

## User Factory

`UserFactory` covers:

- default verified users with the shared test password,
- admin users for protected maintenance/admin surfaces,
- unverified users,
- custom passwords,
- confirmed two-factor authentication state,
- configured primary and secondary demo users.

Demo user factory states read from `config/demo.php` so the login panel, seeders, and tests stay aligned. The primary demo user is an admin; the secondary demo user is a normal user.

## Project And Tag Factories

`ProjectFactory` covers:

- default active projects,
- archived projects,
- explicit active state,
- named and color states,
- `work()` and `home()` demo states.

`TagFactory` covers:

- default user-owned tags,
- named and color states,
- `urgent()` and `waiting()` demo states.

## Goal And Milestone Factories

`GoalFactory` covers:

- default user-owned goals,
- `forProject()` to attach a goal to an owned project while preserving the
  owner boundary,
- explicit titles through `titled()`,
- target dates through `targetDate()`,
- completed and archived states.

`GoalMilestoneFactory` covers:

- default user-owned milestones,
- `forGoal()` to attach a milestone to an owned goal while preserving the
  owner boundary,
- explicit titles and positions,
- target dates,
- pending and completed check-in states.

## Habit And Check-In Factories

`HabitFactory` covers:

- default user-owned daily habits,
- `forGoal()` to attach a habit to an owned goal while preserving the owner
  boundary,
- explicit titles through `titled()`,
- daily and weekly frequency states,
- target-count states,
- archived habits.

`HabitCheckInFactory` covers:

- default check-in rows,
- `forHabit()` to attach a check-in to an owned habit while preserving the owner
  boundary,
- explicit check-in dates through `occurredOn()`,
- `today()` and `yesterday()` states for progress and streak tests.

## Reminder Factory

`ReminderFactory` covers valid placeholder record creation for the current reminder schema. It does not define active, due, sent, failed, retry, or processed states yet because the table has no columns for those concepts.

## Pomodoro Session Factory

`PomodoroSessionFactory` covers:

- default running focus sessions,
- `forTodo()` to attach a session to an owned task while preserving the owner
  boundary,
- explicit duration values through `duration()`,
- running, paused, completed, and abandoned lifecycle states,
- demo paused state through `demo()`.

Pomodoro sessions are private resources. They are linked to a task and use the
same `BelongsToUser` concern as todos, projects, goals, habits, and saved
views.

## Time Entry Factory

`TimeEntryFactory` covers:

- default completed entries,
- `forTodo()` and `forProject()` ownership-safe relationship helpers,
- manual entries through `manual()`,
- completed timer entries through `timer()`,
- active resumable timers through `running()`,
- discarded timer rows through `discarded()`,
- Pomodoro-derived entries through `fromPomodoro()`,
- demo notes through `demo()`.

Time entries are private resources. They are scoped by `user_id`, can link to a
task and/or project, and preserve Pomodoro provenance through
`pomodoro_session_id` when a completed focus session is converted into tracked
time.

## Saved Todo View Factory

`SavedTodoViewFactory` covers:

- default user-owned saved task views with normalized empty criteria,
- `dueToday()` views for the active due-today bucket,
- `urgent()` views for urgent priority focus,
- `completed()` views for the completed lifecycle tab,
- explicit `criteria()` overrides for edge-case and stale-criteria tests.

Saved-view criteria stores only bounded URL state. Factory states do not create
shared/global visibility and remain owner-scoped through `user_id`.

## Todo Factory

`TodoFactory` covers:

- active, completed, archived, archived-completed, and soft-deleted Trash lifecycle states,
- inbox and triaged states through `inbox()` and `triaged()`,
- focus candidate state through `focusCandidate()`,
- low, normal, high, and urgent priority shortcuts,
- due today, overdue, upcoming, no due date, explicit due date, and max-length title states,
- project ownership helper through `forProject()`,
- goal ownership helpers through `forGoal()` and `forMilestone()`,
- habit ownership helper through `forHabit()`,
- tag ownership helpers through `forTag()` and `withTags()`.

The tag helpers avoid cross-user attachment by attaching only tags that share the todo owner.

Inbox factory state marks active, unprojected, unscheduled tasks with
`inbox_captured_at`; `triaged()` clears that marker for tests that need normal
active tasks that have left the Inbox.

Focus candidate state marks an active high-priority due-today task so focus
query and quick-action tests can create important work without repeating
priority/date setup.

## Todo Checklist Item Factory

`TodoChecklistItemFactory` covers:

- default contained checklist rows whose generated parent task shares the same
  owner,
- `forTodo()` for explicit parent task attachment,
- pending and completed states with matching `completed_at` behavior,
- explicit `position()` ordering,
- max-length title coverage through `longTitle()`.

Checklist rows are private resources and use the same `BelongsToUser` concern as
todos, projects, tags, and saved views.

## Todo Template Factory

`TodoTemplateFactory` covers:

- default user-owned private task templates,
- `task()`, `project()`, `checklist()`, and `routine()` template kinds,
- `private()` and `shared()` visibility states,
- due-offset defaults through `dueIn()`,
- 10-item checklist edge coverage through `heavyChecklist()`,
- max-length template names through `longName()`.

Shared template visibility is seed/test data for the later collaboration steps;
templates remain owner-scoped until member roles exist.

## Verification

`tests/Feature/FactoryCoverageTest.php` creates each tracked model through its factory and verifies the important current states and ownership boundaries.
