# Seeding Strategy

Step 012 covers the committed model set:

- `User`
- `Project`
- `SavedTodoView`
- `Tag`
- `Todo`
- `TodoChecklistItem`

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
- contained checklist rows on due-today, overdue, upcoming, and archived tasks
  so task detail pages show progress and locked archived checklist behavior.
- three saved views per user: `Today focus`, `Urgent work`, and
  `Waiting on others`.

Step 041's calendar view reuses that catalog: the seeded due-today, overdue,
upcoming, and no-due-date tasks give the local `/todos/calendar` page immediate
month and unscheduled examples without adding new model rows or changing the
stable seeder counts. Reminder and recurrence rows remain deferred until those
schemas exist.

## Idempotency

Seeders are idempotent for the current demo catalog. Re-running them updates existing demo records instead of creating duplicate users, tags, projects, saved views, or seeded task titles.

Checklist rows are upserted per seeded task/title and keep positions stable when
the seeder is run again.

Placeholder reminder rows are intentionally excluded from the current catalog because they would not be owned by a user or connected to a task.

## Production Safety

Known demo users are not created outside safe environments. A production deployment should use:

```text
APP_ENV=production
RUFLO_DEMO_LOGIN_PANEL=false
```

The normal application must not depend on Artisan or seed commands during everyday hosted usage. Seeding is setup/demo data only.
