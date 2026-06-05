# Authorization & Private Workspace Model

This document specifies the access-control model for the Todo application. It
is the contract every future feature (projects, tags, reminders, recurring
tasks, dashboard, search, filters, bulk actions, collaboration) must follow.
If a change cannot satisfy these rules, the change is wrong, not the rules.

## Core invariant

> One user must never view, search, count, create-for, edit, complete, reopen,
> archive, restore, delete, or otherwise infer another user's private todo
> data — unless a future collaboration feature grants it through an explicit,
> authorized, tested permission.

Private by default. Shared only by explicit permission. Every shared access
re-checked. Every shared access testable.

## The workspace boundary

There is no separate `Workspace` model yet. **The owning `User` is the
workspace.** Ownership is represented by a `user_id` foreign key on each
todo-related resource.

This is deliberate: it keeps the current product single-user-private while
leaving a clean seam. When shared workspaces arrive, the ownership boundary
moves from "the owner user" to "an authorized workspace member", and the
checks below change in exactly one place each (the policy and the ownership
scope) rather than across the whole codebase.

## Where the rules live (single sources of truth)

| Concern | Location | Rule |
| --- | --- | --- |
| Ownership scoping | `App\Models\Concerns\BelongsToUser` (`scopeOwnedBy`, `isOwnedBy`) | The only way to scope a query or check ownership. |
| Read boundary | `App\Queries\Todos\TodoListQuery` | The only place todos are read for the UI. Always owner-scoped. |
| Per-action decisions | `App\Policies\TodoPolicy` | The only place "may this user do this?" is answered. |
| Policy binding | `#[UsePolicy(TodoPolicy::class)]` on `App\Models\Todo` | Explicit, greppable mapping — not naming-convention magic. |
| Mutations | `App\Actions\Todos\*` | Assign ownership from the authenticated user; never from request input. |
| Dashboard summary | `App\Queries\Dashboard\DailySummaryQuery` | Counts tasks, active projects, and tags through owner-scoped queries only. |

Do not scatter authorization across components, views, or query callers.
Reuse these.

## Ownership assignment

Ownership is assigned by backend logic only:

- New todos are created through the owning relationship
  (`$user->todos()->create(...)` inside `CreateTodo`).
- `user_id` is **not** in the model's `#[Fillable]` list, so mass assignment
  cannot set or change it. A test (`TodoOwnershipTest`) proves a submitted
  `user_id` is ignored.
- Ownership transfer is **not** a feature. If it ever becomes one, it must be a
  dedicated, authorized action with activity tracking — never a casual update.

## Query scoping

Every read of todo data must start from `TodoListQuery` (or apply `ownedBy`
directly). Consequences that are mandatory, not optional:

- Lists, single-record lookups, and counters are all owner-scoped.
- Client-supplied IDs are untrusted. `findVisibleFor()` resolves them through
  the owner-scoped query, so another user's ID returns **not found** — the
  record's existence never leaks.
- Aggregates (the remaining/completed summary) are computed inside the scope,
  so counters can never include another user's tasks.
- Related project and tag labels are constrained to the same owner before
  eager loading. Normal actions already prevent foreign links, but malformed
  legacy rows or manual database edits still must not leak names in the UI.

The safest query is one that never sees unauthorized data in the first place.

## Authorization at the action boundary

The Livewire component authorizes **before** delegating to an action:

- `viewAny` / `create` / `clearCompleted` — class-level abilities, allowed for
  any authenticated user (they only ever touch that user's own workspace).
- `view` / `update` / `complete` / `delete` / `restore` — per-record abilities,
  owner-only, returning `denyAsNotFound()` so forbidden access is
  indistinguishable from a missing record.
- `forceDelete` — disabled for everyone. Permanent deletion is not a feature
  yet; when designed it must be protected more strictly than soft delete.

Backend authorization is the real security. Frontend hiding of buttons is UX
only and is never sufficient.

## Route protection

All private todo routes live behind `['auth', 'verified']` in
`routes/web.php`. Guests are redirected to login; unverified users to
verification. There are no public routes that touch private data. Future todo
routes must join the same protected group — never a standalone public route
"just for testing".

## Error behavior (no leakage)

- Forbidden private records resolve as **not found** (404-style), never
  "forbidden — this belongs to someone else".
- Error and validation messages are translatable and never echo another user's
  task content.
- Page titles, navigation, counters, and (future) search/filter dropdowns must
  only ever reflect the current user's workspace.

## Preparation for later steps

These are documented now so later steps inherit the model instead of bolting
it on:

- **Dashboard** — every current widget/counter uses
  `DailySummaryQuery::for($user)` or `TodoListQuery::summaryFor($user)`; if
  cached later, cache keys must be per-user so data never mixes.
- **Search & filters** — ownership is applied at the query level before any
  text/status/priority filtering; invalid filter input is validated and must
  never widen the scope.
- **Bulk actions** — never trust a submitted set of IDs. Re-scope every
  selected ID to the owner and authorize each before acting; a foreign ID in
  the payload is silently excluded, not processed.
- **Activity history** — visible only to users who can access the related
  record; scoped exactly like the record itself.
- **Notifications/reminders** — target only the owning user; a notification is
  a message, not a permission, so opening its link re-checks authorization.
- **Collaboration** — when added, the policy and `BelongsToUser` boundary
  expand to "authorized member" in one place each; do not hardcode
  "exactly one user" assumptions elsewhere.

## Testing requirements

Access-control tests are mandatory and must use **at least two users**.
`TodoOwnershipTest` and `TodoTest` already cover: policy resolution, owner
allow / non-owner deny for every per-record ability, not-found leakage
behavior, mass-assignment refusal, owner-scoped queries and counters, and
guest redirects. Every future todo capability must add the matching
cross-user denial test before it is considered done.

`PrivateWorkspaceModelTest` locks the Step 017 contract: todo-related private
models must use the shared owner concern, private policies must hide foreign
records as not found, dashboard counts must be user-scoped, malformed
cross-user project/tag links must not hydrate foreign labels, and placeholder
reminders remain inaccessible until their real owner/schedule schema exists.
