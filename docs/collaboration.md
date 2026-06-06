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

## Link-Only Invites

Step 069 adds `project_invitations` for copyable, manually shared invite links.
RuFlo does not send invite emails. Users with member-management access create a
signed HTTPS link from the project detail page, copy it, and share it through
their own trusted channel.

Invite links store an encrypted token plus a token hash for lookup. Pending
links can be copied until they expire or are cancelled. Accepted, cancelled,
and expired links do not grant access.

Invite acceptance uses the protected signed route:

```text
https://ruflo.test/project-invitations/{token}
```

The accept page is authenticated and verified. It intentionally shows only a
generic invite screen, requested role, status, and expiration before acceptance;
the project name, tasks, and members remain hidden until the invite creates an
active membership for the accepting account.

Acceptance re-checks that:

- the invite is still pending,
- the project is still active,
- the stored role is still assignable,
- the inviter can still manage project members,
- the accepting user is not the project owner.

The membership write reuses `AddProjectMember`, so owner-role and owner-member
guards stay centralized.

## Member Management

Step 070 adds role editing and member removal to the project detail page for
owners and managers. Editors and viewers can see the member list, scope badge,
and their own role, but they do not receive role-edit or removal controls.

Role changes use the dedicated `UpdateProjectMemberRole` action, the
`UpdateProjectMembershipRequest` rule source, and the `ProjectMemberRole`
custom rule. Only `manager`, `editor`, and `viewer` are assignable from the UI;
the project owner role remains derived from `projects.user_id` and cannot be
assigned or edited as a membership row.

Member removal reuses `RemoveProjectMember`, sets `removed_at`, and immediately
removes access through `ProjectAccess`. Old project and task links for removed
members return not found instead of revealing project names, task titles, or
member details.

Every submitted membership id is re-queried through
`ProjectMembershipQuery::findActiveForProject()` before authorization and
mutation, so stale, removed, and foreign membership rows fail closed.

## Seeding

`ProjectMembershipSeeder` runs only in local, testing, or demo environments. It
creates an idempotent demo sharing graph between the configured demo users:

- Avery shares Work with Morgan as editor.
- Avery shares Home with Morgan as viewer.
- Morgan shares Work with Avery as manager.

`ProjectInvitationSeeder` also runs only in local, testing, or demo
environments. It creates deterministic pending, accepted, cancelled, and
expired link-only invite examples for the configured demo users so the project
detail page immediately shows every invite state after seeding.

## Restricted Hosting

Collaboration reads and writes happen inside normal authenticated web requests.
The foundation requires no cron, queue workers, supervisors, terminal access,
Artisan commands during normal usage, emails, paid services, hosted services, or
external identity provider.

Link-only invites follow the same restricted-hosting contract. Creation,
cancellation, and acceptance are normal web requests. There is no cron, queue
worker, supervisor, terminal action, Artisan command during normal usage, email
provider, hosted invite service, or paid dependency.

Member role changes and removals are normal authenticated Livewire requests.
They require no cron, queue worker, supervisor, terminal action, Artisan command
during normal usage, hosted service, email provider, or paid dependency.
