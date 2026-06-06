# Reminders

Step 054 adds web-triggered task reminders at `/todos/reminders`.

## Model

`reminders` are private rows owned by `user_id` and linked to one `todo_id`.
They store `remind_at`, `status`, `processed_at`, `skipped_at`,
`skipped_reason`, and `last_error`.

Statuses are defined by `App\Enums\ReminderStatus`:

- `pending` for scheduled reminders that still need processing,
- `processed` for reminders that created a database notification,
- `skipped` for reminders that could not be processed because preferences were
  paused, the task was no longer actionable, or processing failed.

One reminder per user/task is stored. Scheduling the same task again updates the
existing reminder and resets it to pending.

## Web Processing

Due reminders process only from authenticated web requests:

- opening the dashboard,
- opening `/todos/reminders`,
- pressing the Process due button on `/todos/reminders`.

`ProcessDueReminders` delegates to `RunManualWebProcess` through
`ProcessDueRemindersProcess`, so each browser request processes a bounded
owner-scoped chunk and reports matched, processed, skipped, failed, and
remaining counts. Retry and resume are explicit: run the page action again.

No cron, queue worker, supervisor, shell command, Artisan command, email
delivery, paid service, or hosted automation provider is required during normal
usage. Reminder delivery currently writes database notifications only.

## Scheduling

The class-based Livewire page validates task selection with `OwnedTodo` and the
browser `datetime-local` value with `ReminderAt`. `SyncTodoReminder` repeats the
backend guards and only accepts active, non-archived, non-trashed tasks owned by
the authenticated user.

Completed, archived, deleted, missing, or foreign task ids do not create
actionable reminders. Due reminders for tasks that later become completed,
archived, or deleted are marked skipped and do not notify.

## Preferences

`users.reminders_enabled` controls whether due reminders should process for the
account. When disabled, due pending reminders are marked skipped with
`preferences_disabled` and no notification is created.

## Privacy

All reminder reads start from the current user's owner scope through
`ReminderListQuery` or the user's `reminders()` relationship. Reminder policy
record abilities are owner-only and deny foreign records as not found.
Notification action links route back to protected task pages, where the task is
re-authorized by the existing owner-scoped task detail lookup.

## Demo Data

`TodoSeeder` creates three reminders per safe demo user:

- one due pending reminder,
- one future pending reminder,
- one skipped archived-task reminder.

The production seeder guard remains unchanged; known demo users and reminder
demo rows are not created in production-like environments.

## Tests

`tests/Feature/ReminderSystemTest.php` covers route rendering, owner privacy,
scheduling validation, bounded chunk processing, database notifications,
completed/archived/deleted skips, disabled preferences, dashboard-triggered
processing, and restricted-hosting defaults.
