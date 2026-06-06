# Notification Center

Step 055 adds a private in-app notification center at `/notifications`.

## Runtime Contract

- Notifications are stored in Laravel's `notifications` table and delivered
  through database channels only.
- The center is a class-based Livewire page behind `auth` and `verified`.
- Reads flow through `App\Queries\Notifications\NotificationInboxQuery`, scoped
  by the authenticated user's `notifiable_type` and `notifiable_id`.
- Read, unread, and mark-all-read actions re-query the notification through the
  same owner scope before mutating state.
- Action URLs are display hints only. The center renders relative links and
  same-host links for `https://ruflo.test` only after hiding external,
  protocol-relative, and unsupported-scheme links. Known task links are
  pre-checked against the current user's task scope, and target routes must
  still authorize access before showing private records.
- Step 072 task-comment notifications are database-only. When a shared
  participant comments on a task, the task owner receives a
  `todo-comment-added` database notification. The comment author and unrelated
  shared viewers are not notified in this step.

## UI

- The page uses Flux cards, badges, buttons, and pagination inside the shared
  page header/container components.
- Tabs filter all, unread, and read notifications without loading an unbounded
  list.
- Empty states, labels, button text, status badges, toasts, and fallback
  messages live in `lang/en/notifications.php`.
- Comment notification titles and messages also live in
  `lang/en/notifications.php`; author fallback copy remains in
  `lang/en/todos.php`.

## Restricted Hosting

The notification center does not require email, a push provider, cron, queue
workers, supervisors, shell access, Artisan commands, or paid services during
normal usage. It reads and updates database notifications synchronously during
authenticated browser requests.

Task-comment notifications follow that same contract. They are created
synchronously inside the comment creation request and do not require email,
queues, workers, cron, supervisors, shell access, Artisan commands during
normal usage, hosted services, or paid providers.

## Verification

- `NotificationCenterTest` covers private rendering, read/unread state changes,
  mark-all-read owner scoping, same-host link filtering, protocol-relative and
  unsupported-scheme filtering, stale private task-link hiding, known task-link
  prechecks, and target-route authorization.
- `TaskCommentTest` covers Step 072 owner database notifications and confirms
  inert `@mention` text does not notify unrelated users before the dedicated
  mentions step.
- Guest route, domain, localization, and architecture coverage include the
  protected `/notifications` surface.
