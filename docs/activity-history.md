# Activity History

Step 066 adds a private activity history for meaningful task-domain events.
Step 067 embeds a focused task timeline on private task detail pages.

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
- The authenticated sidebar navigation points to the protected activity route.
- All visible copy lives in `lang/en/activity.php` and existing navigation
  language files.

## Task Timeline

- Task detail pages render a class-based `todos.task-timeline` Livewire
  component after the task summary.
- The component re-resolves the parent task through `TodoListQuery`, authorizes
  `view`, then reads only that task's records through
  `ActivityFeedQuery::forTodo()`.
- The per-task read path is backed by the
  `activity_records_user_subject_time_index` composite index on owner, subject,
  and event time.
- The embedded timeline uses bounded Load more pagination and shares
  `ActivityFormatter` with the full `/activity` page so event names, icons,
  colors, actor labels, and change summaries stay consistent.
- The task timeline intentionally does not render subject links. This prevents
  stale, deleted, or no-longer-visible subject references from becoming
  clickable from the detail page.

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

Step 067 also adds no new user-submitted data. The only interactive input is a
bounded Load more action on an already owner-scoped task timeline.
