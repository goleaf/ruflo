# Activity History

Step 066 adds a private activity history for meaningful task-domain events.

## Runtime

- Activity records are written synchronously from existing task domain events.
- The feature does not use queues, cron, workers, supervisors, terminal access,
  Artisan commands, hosted audit services, or paid analytics providers.
- The `/activity` page is a class-based Livewire page behind `auth` and
  `verified` middleware.
- The feed uses a bounded load-more interaction instead of background polling
  or infinite server work.

## Privacy

- Every `ActivityRecord` has a `user_id` owner and is read through
  `ActivityFeedQuery`.
- Activity records are authorized with `ActivityRecordPolicy`.
- Deleted task activity stores a safe title snapshot and hides stale task links
  unless the current user can still see the task.
- Updated task metadata stores only a small allow-listed set of safe old/new
  values. No-op updates are ignored to avoid noisy history.

## User Interface

- The activity page renders Flux summary cards, timeline badges, actor/time
  copy, translated event descriptions, an empty state, and a load-more button.
- Navigation and command palette entries point to the protected activity route.
- All visible copy lives in `lang/en/activity.php` and existing navigation
  language files.

## Factories And Seeding

- `ActivityRecordFactory` covers generic records, task-created, task-updated,
  task-deleted, and completed-cleared states.
- `ActivityRecordSeeder` is limited to `local`, `testing`, and `demo`
  environments and seeds realistic demo history for the configured demo users
  when they already have tasks.

## Validation

Step 066 adds no new user-submitted form input. Existing task create/edit and
checklist validation remains the input boundary. Activity recording listens to
already validated domain actions and sanitizes stored metadata before writing.
