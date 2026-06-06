# Collaboration Foundation

Step 068 introduces project-level collaboration without replacing the private
workspace model.

## Access Model

Projects still have one owner through `projects.user_id`. The owner is not
stored as a membership row. Shared access is represented by active
`project_memberships` rows:

- `manager` can view the project, update project details, manage members, and
  manage shared project tasks.
- `editor` can view the project and edit shared project tasks.
- `viewer` can view the project and shared project tasks.
- removed memberships have `removed_at` set and no longer grant access.

`App\Support\Projects\ProjectAccess` is the central role resolver used by
project and todo policies. Future invite and member-management work must reuse
that resolver instead of duplicating role checks in Livewire components or
Blade views.

## Query Boundaries

`ProjectListQuery::accessibleFor()` and `findAccessibleFor()` return owned
projects plus projects with an active membership. Owner-only management pickers
continue to use `visibleFor()`.

`TodoListQuery::findVisibleFor()` now allows a task to be viewed or edited
through an active project membership only when the task still belongs to the
project owner. This preserves the original owner boundary and prevents malformed
cross-owner project links from widening task access.

Project detail pages read shared project tasks through the project owner scope
and hide owner-only filter shortcuts for shared members.

## UI

The project detail page shows:

- private or shared scope,
- the current user's project role,
- the project owner,
- active members and their roles.

Step 068 intentionally does not create invite links or member-management forms.
Those are reserved for Steps 069 and 070 so link-only invite and role-editing
flows can have their own request classes, validation rules, tests, and docs.

## Seeding

`ProjectMembershipSeeder` runs only in local, testing, or demo environments. It
creates an idempotent demo sharing graph between the configured demo users:

- Avery shares Work with Morgan as editor.
- Avery shares Home with Morgan as viewer.
- Morgan shares Work with Avery as manager.

## Restricted Hosting

Collaboration reads and writes happen inside normal authenticated web requests.
The foundation requires no cron, queue workers, supervisors, terminal access,
Artisan commands during normal usage, emails, paid services, hosted services, or
external identity provider.
