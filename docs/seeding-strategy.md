# Seeding Strategy

Step 012 covers the committed model set:

- `User`
- `Project`
- `Tag`
- `Todo`

Future models for reminders, recurrence, comments, attachments, activity, invites, settings, and collaboration are not seeded yet because those committed models do not exist yet.

## Seeders

`DatabaseSeeder` calls seeders in this order:

1. `DemoUserSeeder`
2. `TodoSeeder`

`DemoUserSeeder` creates the configured demo users only when the app is running in a safe environment: `local`, `testing`, or `demo`, and when the demo login panel is enabled. The first configured demo user is seeded as an admin for protected local maintenance access; the second configured demo user is seeded as a normal account for denial and isolation checks.

`TodoSeeder` creates a private workspace for every existing user:

- active `Work` and `Home` projects,
- archived `Old plans` project,
- `urgent` and `waiting` tags,
- active, due-today, overdue, upcoming, completed, archived, and archived-completed tasks.

## Idempotency

Seeders are idempotent for the current demo catalog. Re-running them updates existing demo records instead of creating duplicate users, tags, projects, or seeded task titles.

## Production Safety

Known demo users are not created outside safe environments. A production deployment should use:

```text
APP_ENV=production
RUFLO_DEMO_LOGIN_PANEL=false
```

The normal application must not depend on Artisan or seed commands during everyday hosted usage. Seeding is setup/demo data only.
