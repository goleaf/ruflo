# Changelog

## 2026-06-06 - Step 037 Sorting

### Implemented

- Added deterministic tie-breakers to every todo sort path so pagination remains stable for tied created, updated, due-date, priority, project, and title values.
- Added translated Flux chips for non-default or tampered sort and direction URL state.
- Kept sorting owner-scoped through `TodoListQuery`, with allow-listed sort keys and safe direction fallback.

### Testing

- Added `TaskSortingTest` for owner-scoped title sorting, due-date null-last ordering, project/priority sorting, created-at tie-breakers, URL-backed sort pagination, reset behavior, and tampered sort/direction fallback.
- Rechecked the focused sorting test after fixing an invalid Flux icon name caught by the first test run.

### Documentation

- Updated `docs/task-organization.md` and `docs/todo-foundation.md` with the Step 037 sorting contract.

## 2026-06-06 - Step 036 Filters

### Implemented

- Added `TodoFilters::hasInvalidFilter` so the query layer can return an empty result for invalid filter state.
- Hardened invalid lifecycle, priority, and active-tab due-bucket query values so they do not silently widen a filtered task list.
- Kept valid project, tag, priority, due, search, sorting, and pagination filters composable through the existing Livewire/Flux filter panel and owner-scoped query object.

### Testing

- Added `TaskFilterTest` for composed private filters, filter chips, filter pagination, invalid scalar filters, invalid lifecycle state, and reset behavior.
- Rechecked search, organization, ownership scoping, due-date, private-view, dashboard, route-protection, and architecture coverage.

### Documentation

- Updated `docs/task-organization.md` and `docs/todo-foundation.md` with the Step 036 filter contract.

## 2026-06-06 - Step 035 Search

### Implemented

- Added translated active filter chips for search and the existing list filters inside the Flux filter panel.
- Kept search self-hosted through the existing owner-scoped `TodoListQuery` title search with bound `LIKE` parameters and escaped wildcards.
- Hardened non-numeric project/tag query-string values so they resolve to the existing empty-result path instead of widening a searched list.

### Testing

- Added `TaskSearchTest` for owner-scoped search privacy, literal `%` searches, search pagination, reset behavior, translated empty states, and tampered query parameters.
- Rechecked organization, ownership scoping, private views, due-date, dashboard, route-protection, and architecture coverage.

### Documentation

- Updated `docs/task-organization.md` and `docs/todo-foundation.md` with the Step 035 search contract.

## 2026-06-06 - Step 034 Upcoming view

### Implemented

- Added `App\Livewire\Todos\Upcoming` as a protected class-based Livewire page at `todos.upcoming`.
- Added owner-scoped `TodoListQuery::upcomingFor()` and `findUpcomingFor()` boundaries for future-dated task reads and page actions.
- Rendered a translated Flux Upcoming page with due-date badges, project/tag links, pagination, empty state, and a dashboard shortcut.

### Testing

- Added `UpcomingViewTest` for guest/unverified redirects, owner-only future-due rendering, active-only exclusions, translated empty state, focused completion scope, and route/component guardrails.
- Rechecked Today, Overdue, due-date, private-view, dashboard, domain, route-protection, and architecture coverage.

### Documentation

- Updated `docs/task-organization.md`, `docs/domain-readiness.md`, and `docs/todo-foundation.md` with the Step 034 upcoming-view contract.

## 2026-06-06 - Step 029 Tags and labels

### Implemented

- Added `App\Rules\Tags\TagName` so tag names must contain visible content after normalization.
- Hardened `CreateTag` so direct backend callers cannot persist an empty normalized tag name.
- Linked rendered tag badges from task list, task detail, and project detail pages to the existing owner-scoped tag filter.

### Testing

- Expanded `TagTest` for whitespace-only Livewire validation, direct action bypass protection, normalized duplicate behavior, current-user-only tag filter links, delete behavior, and cross-user isolation.
- Rechecked organization, create/edit, ownership, private view, project detail, validation-rule architecture, authorization, factory, seeder, and architecture suites.

### Documentation

- Updated `docs/task-organization.md`, `docs/validation-rules.md`, and `docs/todo-foundation.md` with the Step 029 tag validation and filter-link contracts.

## 2026-06-06 - Step 028 Projects and lists

### Implemented

- Added `App\Livewire\Projects\Show` as an owner-scoped, class-based Livewire project detail page.
- Added the protected `projects.show` route and linked project badges from task lists and task detail pages.
- Added `TodoListQuery::forProjectDetail()` and `projectSummaryFor()` so project detail task lists and counts stay owner-scoped and paginated.
- Kept archived projects readable on their detail page while keeping them out of active task assignment/filter surfaces.

### Testing

- Added `ProjectDetailTest` for guest redirects, unverified redirects, owner-only rendering, foreign not-found behavior, archived project detail behavior, translated empty states, project badge links, direct Livewire foreign-id denial, and route/component guardrails.
- Rechecked project management, task organization, task private views, ownership query scoping, authorization policies, architecture, factory, and seeder coverage.

### Documentation

- Updated `docs/task-organization.md` and `docs/authorization.md` with the Step 028 project detail contract.

## 2026-06-06 - Step 027 Task lifecycle state machine

### Implemented

- Added `TodoTransition` to name every lifecycle transition explicitly.
- Added `TodoLifecycleStateMachine` as the single accepted-source-state and target-bucket map.
- Routed complete, reopen, archive, unarchive, delete, restore-from-trash, and update actions through the state machine before lifecycle mutation.
- Added language-backed trash-specific invalid-transition exceptions so direct action calls cannot complete, reopen, archive, unarchive, or edit deleted tasks.

### Testing

- Added `TaskLifecycleStateMachineTest` for accepted states, target buckets, idempotent no-ops, direct-action rejection for trashed tasks, and translated exception messages.
- Rechecked adjacent completion/reopening, archive/unarchive, deletion/trash, editing, lifecycle, organization, ownership, policy, and architecture suites.

### Documentation

- Updated `docs/task-lifecycle.md` and `docs/todo-foundation.md` with the centralized Step 027 state-machine contract.

## 2026-06-06 - Step 026 Task deletion and trash behavior

### Implemented

- Added a user-facing Trash lifecycle bucket backed by soft-deleted `todos.deleted_at` records.
- Added `RestoreDeletedTodo`, `BulkRestoreDeletedTodos`, and `TodoRestoredFromTrash` for explicit restore-from-trash behavior.
- Added `TodoListQuery::findTrashedFor()` and trash counts in `summaryFor()`/dashboard summaries while keeping all reads owner-scoped.
- Changed `BulkDeleteTodos` to reuse `DeleteTodo` so delete events are not skipped by a mass update.
- Kept permanent deletion disabled through `TodoPolicy::forceDelete` and omitted force-delete UI.
- Seeded one trashed demo task per local/demo user workspace.

### Testing

- Added `TaskDeletionTrashTest` for delete/restore actions, event dispatch, idempotency, Trash tab privacy, foreign-id denial, bulk delete/restore, selected deleted-task validation, force-delete denial, and trash query boundaries.
- Updated lifecycle, organization, ownership, authorization, factory, seeder, dashboard, private-workspace, architecture, archive/unarchive, and completion/reopening tests for the Trash bucket.

### Documentation

- Updated `docs/task-lifecycle.md`, `docs/authorization.md`, `docs/task-organization.md`, `docs/todo-foundation.md`, `docs/seeding-strategy.md`, and `docs/factory-coverage.md` with the Step 026 deletion/trash contract.

## 2026-06-06 - Step 025 Task archive and restore

### Implemented

- Kept task archive reversal explicit with `UnarchiveTodo`, `TodoUnarchived`, `unarchiveTodo`, and `bulkUnarchive`.
- Replaced the old task archive-reversal bulk action with `BulkUnarchiveTodos` so archive reversal is not confused with future trash restore behavior.
- Changed bulk archive and unarchive flows to reuse the single-task transition actions so events and idempotency rules stay consistent.
- Preserved completion state across archive/unarchive and kept archived task completion/edit transitions rejected.

### Testing

- Added `TaskArchiveRestoreTest` for direct actions, Livewire transitions, event dispatch, idempotency, completion-state preservation, foreign-id denial, bulk archive/unarchive, scoped lookup status, and UI label wiring.
- Updated lifecycle, organization, ownership, authorization, factory, seeder, dashboard, query-scoping, and completion/reopening suites for the explicit unarchive contract.

### Documentation

- Updated `docs/task-lifecycle.md`, `docs/authorization.md`, `docs/task-organization.md`, and `docs/todo-foundation.md` with the Step 025 archive/unarchive contract.

## 2026-06-06 - Step 024 Task completion and reopening

### Implemented

- Replaced the generic completion boundary with explicit `CompleteTodo` and `ReopenTodo` actions.
- Added `TodoCompleted` and `TodoReopened` domain events for future activity history and reminder integrations.
- Kept the row checkbox UX, but made it call state-specific Livewire methods with translated complete/reopen accessibility labels.
- Changed bulk completion to reuse the `CompleteTodo` transition so completion events are not skipped by a mass update.
- Made duplicate complete/reopen calls idempotent no-ops and kept archived completion changes rejected.

### Testing

- Added `TaskCompletionReopeningTest` for direct actions, Livewire transitions, event dispatch, idempotency, archived rejection, foreign-id denial, bulk completion, detail lookup status, and UI label wiring.
- Updated existing lifecycle, ownership, architecture, and core creation tests from the old generic completion boundary to explicit complete/reopen transitions.

### Documentation

- Updated `docs/task-lifecycle.md`, `docs/authorization.md`, and `docs/todo-foundation.md` with the Step 024 completion/reopening contract.

## 2026-06-06 - Step 023 Task editing

### Implemented

- Trimmed edited task titles at the `UpdateTodo` write boundary, matching the creation action.
- Kept edit mutations limited to editable details: title, priority, due date, project, and tags.
- Preserved lifecycle separation so editing cannot complete, archive, unarchive, or delete a task.
- Added edit-modal validation error placement beside priority, due date, project, and tampered tag fields.
- Updated `TodoUpdated` documentation for future activity-history readiness.

### Testing

- Added `TaskEditingTest` for full detail edits, action-level trimming, organization re-scoping, foreign edit denial, invalid edit preservation, archived-task rejection, and modal error placement.
- Rechecked adjacent lifecycle, organization, ownership, query-scoping, creation, private-view, project, tag, and architecture suites.

### Documentation

- Updated `docs/task-lifecycle.md` and `docs/task-organization.md` with the Step 023 editing contract.

## 2026-06-06 - Step 022 Task list and private task views

### Implemented

- Added a class-based Livewire task detail page at `todos.show` (`/todos/{todo}`) behind the existing authenticated and verified todo route group.
- Resolved task detail records through `TodoListQuery::findVisibleFor()` with a locked route id so guessed or foreign IDs return not found without leaking content.
- Linked rendered task titles in the private list to their owner-scoped detail pages.
- Added translated detail-page copy and metadata labels for status, due date, project, tags, created, and updated timestamps.

### Testing

- Added `TaskPrivateViewsTest` for guest redirects, unverified redirects, own-task rendering, foreign 404 behavior, list-link privacy, direct Livewire foreign-id denial, and route/component guardrails.
- Extended the domain readiness route contract for `todos.show`.
- Rechecked adjacent todo list, ownership, lifecycle, organization, project, tag, creation, route-protection, and domain suites.

### Documentation

- Updated `docs/authorization.md`, `docs/task-organization.md`, and `docs/domain-readiness.md` with the Step 022 private detail-view contract.

## 2026-06-06 - Step 021 Core task creation

### Implemented

- Removed completion state from todo mass assignment so task creation cannot set lifecycle state accidentally.
- Changed the completion action to set `is_completed` explicitly through the lifecycle action.
- Trimmed task titles at the `CreateTodo` write boundary, even when a backend caller constructs `TodoData` manually.
- Added create-form validation error placement beside priority, due date, project, and tampered tag fields while keeping the class-based Livewire/Flux UI.

### Testing

- Added `CoreTaskCreationTest` for direct action creation, event dispatch, bypassed-validation organization scoping, mass-assignment guards, lifecycle completion, long-title validation, and create-form error placement.
- Rechecked adjacent todo, lifecycle, ownership, organization, architecture, dashboard, and route-protection suites.

### Documentation

- Updated `docs/todo-foundation.md`, `docs/task-lifecycle.md`, and `docs/task-organization.md` with the Step 021 creation contract.

## 2026-06-06 - Step 020 Guest and route protection

### Implemented

- Enabled Laravel's `MustVerifyEmail` contract on the `User` model so the existing `verified` middleware blocks unverified users from private verified routes.
- Added route-protection regression coverage for guest redirects, unverified-user redirects, password confirmation, the maintenance admin gate, protected route middleware, and demo password hash leakage.
- Kept the demo login panel inside Fortify's normal login flow with no production bypass or custom authentication route.

### Testing

- Added `GuestRouteProtectionTest` covering every current private application route.
- Rechecked the affected Fortify auth, demo login, dashboard, todo, settings, maintenance, setup, and domain readiness suites.

### Documentation

- Updated `docs/auth-login-ux.md` and `docs/authorization.md` with the Step 020 route-protection contract.

## 2026-06-06 - Step 019 Authorization policies

### Implemented

- Added an explicit todo `reopen` policy ability and made the Livewire completion flow authorize `complete` or `reopen` based on task state.
- Bound the placeholder `Reminder` model to its deny-all policy with an explicit `UsePolicy` attribute.
- Standardized owner-only policy checks for todos, projects, and tags through the shared `isOwnedBy()` helper.
- Added explicit tag `restore` and `forceDelete` denials for unsupported destructive actions.

### Testing

- Added `AuthorizationPoliciesTest` covering policy resolution, owner/non-owner matrices, not-found denials, class-level todo bulk abilities, unsupported destructive denials, and deny-all reminders.
- Extended todo ownership coverage for `reopen` and `archive` abilities.

### Documentation

- Updated `docs/authorization.md` with explicit policy mappings, the separate reopen ability, role limitations, and Step 019 test coverage.

## 2026-06-06 - Step 018 Ownership and query scoping

### Implemented

- Locked server-assigned todo edit identifiers in the class-based Livewire todo workspace.
- Removed the unscoped edit-form tag reload so edit state uses the owner-constrained `TodoListQuery` result.
- Tightened project and tag URL filters so foreign, archived, or missing IDs return an empty owner-scoped result instead of being applied as query predicates.
- Added Step 018 coverage for picker queries, tampered filters, edit-form tag hydration, locked IDs, and component query delegation.

### Testing

- Added `OwnershipQueryScopingTest` for private query scoping and Livewire ID hardening.
- Rechecked adjacent private workspace, ownership, organization, project, tag, and architecture tests.

### Documentation

- Updated `docs/authorization.md` and `docs/task-organization.md` with invalid-filter empty-state behavior and locked edit-ID expectations.

## 2026-06-06 - Step 017 Private workspace model

### Implemented

- Kept the owning `User` as the private workspace boundary instead of adding a premature `Workspace` model.
- Added `DailySummaryQuery` and wired the existing class-based dashboard Livewire component so dashboard counters are scoped to the authenticated user.
- Hardened `TodoListQuery` relationship hydration so malformed cross-user project/tag links do not leak foreign labels into task rows or edit forms.
- Added private-workspace architecture coverage for owner concerns, explicit policies, dashboard counts, relationship hydration, and the inaccessible reminder placeholder.

### Testing

- Added `PrivateWorkspaceModelTest` for Step 017 privacy invariants.
- Rechecked dashboard, todo ownership, organization, project, and tag behavior through the focused privacy suite.

### Documentation

- Updated `docs/authorization.md` with the dashboard query boundary, relation hydration guard, and Step 017 regression-test contract.

## 2026-06-05 - Step 015 Reusable custom validation rules

### Implemented

- Added reusable todo ownership rule objects for owned active projects, owned tags, and owned todos.
- Applied the rules to task creation/editing, bulk selection validation, and bulk move project targets.
- Changed Livewire request validation to reject forged foreign ids before mutation while keeping action-level owner scoping in place.
- Added translated custom validation messages for todo ownership and active-project checks.
- Removed the unused empty `ReminderAtIsActionable` placeholder rule during the Step 015 recheck.

### Testing

- Updated organization and bulk action tests for stricter custom-rule validation.
- Added architecture coverage that the custom rule inventory is implemented and uses translated failure messages.

### Documentation

- Added `docs/validation-rules.md` with the current rule inventory, translation location, and postponed future-domain rule guidance.
- Added the Step 015 recheck notes for the current custom-rule inventory and future-domain placeholder policy.

## 2026-06-05 - Step 016 English localization and message cleanup

### Implemented

- Moved auth, settings, navigation, dashboard, welcome, setup, maintenance, todo, Livewire title, and action-message copy into English language files.
- Kept localization render-time only with no external translation service dependency.

### Testing

- Added localization coverage for literal English strings passed to translation APIs, Flux toasts, `addError`, and Livewire titles.
- Added static translation-key existence coverage during the Step 016 recheck.

### Documentation

- Added and rechecked `docs/localization.md` with language-file inventory, guardrails, and restricted-hosting behavior.

## 2026-06-05 - Step 014 Dedicated request classes

### Implemented

- Added dedicated auth Form Request classes for registration and password reset validation.
- Rewired the Fortify user creation and password reset actions to consume those request classes as the canonical rule, attribute, and message source.
- Added English auth validation field labels and a translated duplicate-email validation message.

### Testing

- Added Fortify route regression tests for failed registration and failed password reset validation.
- Added direct Form Request coverage for canonical rule keys, authorization, translated attributes, and translated duplicate-email messages.

### Documentation

- Added `docs/request-validation.md` describing the HTTP/Fortify request-class boundary and why Livewire-only forms stay in Livewire form objects.
- Added the Step 014 recheck notes for the current request-driven input inventory and feature-suite placement for translated request tests.

## 2026-06-05 - Step 013 Demo users and login panel

### Implemented

- Re-verified the seeded demo login panel against the current Fortify email-login model.
- Kept quick login buttons as normal Fortify POST `/login` submissions with CSRF protection.
- Documented that demo users display email addresses because this app has no separate username column.

### Testing

- Expanded the login UX test to assert rendered demo roles and per-user descriptions.
- Rechecked both seeded demo accounts authenticate through Fortify and private pages redirect guests to login.

### Documentation

- Updated `docs/auth-login-ux.md` with the email-only login identifier contract.
- Added the Step 013 recheck notes for Fortify routes, Flux demo panel behavior, seeded local hashes, and guest redirects.

## 2026-06-05 - Step 012 Complete seeders for all models

### Implemented

- Split demo user seeding into `DemoUserSeeder`.
- Made demo user seeding safe-environment gated and config-backed.
- Reworked todo workspace seeding to be idempotent for projects, tags, and seeded task titles.
- Seeded active, due-today, overdue, upcoming, completed, archived, and archived-completed task scenarios for each demo user.

### Testing

- Added seeder coverage tests for safe demo creation, per-user workspace content, idempotency, and production no-op behavior.
- Added coverage that the placeholder reminders table remains empty until reminder ownership and scheduling columns exist.

### Documentation

- Added `docs/seeding-strategy.md` with the current model inventory, seeder order, safety gates, and postponed future domains.
- Documented why placeholder reminder rows are intentionally excluded from the current demo seed catalog.

## 2026-06-05 - Step 011 Complete factories for all models

### Implemented

- Expanded user factory states for custom passwords, configured demo users, and existing auth states.
- Expanded project and tag factory states for names, colors, archived/active projects, and common demo labels.
- Added coverage for the current placeholder reminder factory.
- Expanded todo factory states for lifecycle, due-date, priority, max-title, soft-delete, project, and tag relationship scenarios.
- Added relationship helpers that preserve user ownership boundaries for project and tag-backed todos.

### Testing

- Added factory coverage tests for every tracked model and important factory state.

### Documentation

- Added `docs/factory-coverage.md` with the tracked model inventory and state coverage.

## 2026-06-05 - Step 010 Authentication and login UX

### Implemented

- Added a config-backed demo login catalog gated to local, testing, and demo environments.
- Added a seeded-user lookup action so the login page only lists configured demo users that exist in the database.
- Added a translated local/demo login panel with visible email/password details and quick login buttons that post through Fortify.
- Updated the seeder to create known demo users only in safe environments.
- Rechecked the demo login flow after maintenance admin gating so the primary seeded demo user is shown as the admin workspace and the secondary seeded demo user remains a normal isolation workspace.

### Testing

- Added auth login UX tests for safe rendering, production hiding, disabled-panel hiding, seeded-user filtering, seeded admin/normal account roles, and quick login through Fortify.

### Documentation

- Added `docs/auth-login-ux.md` with demo login safety rules and production configuration notes.

## 2026-06-05 - Step 009 Domain and ruflo.test readiness

### Implemented

- Set tracked application, filesystem, mail, and test defaults to `https://ruflo.test`.
- Added URL generation defaults in `AppServiceProvider` so named routes, redirects, signed routes, and storage URLs use the configured HTTPS root.
- Aligned the local ignored `.env` for the secured Herd domain and sync queue mode.

### Testing

- Added domain readiness tests for tracked defaults, named route URLs, protected redirects, signed URLs, and public storage URLs.

### Documentation

- Added `docs/domain-readiness.md` documenting the domain contract and future invite/export/download link rules.

## 2026-06-05 - Step 008 Protected maintenance center

### Implemented

- Added a protected maintenance center at `/settings/maintenance` behind `auth`, `verified`, and `password.confirm`.
- Added an admin gate for the maintenance center using `users.is_admin`.
- Added a maintenance snapshot action that reuses setup health checks and reports web-processing/runtime state.
- Added safe web actions to clear compiled Blade views and flush the application cache.
- Added English maintenance translations and settings navigation.

### Testing

- Added maintenance center tests for route protection, admin denial, rendering, cache flush, compiled-view cleanup, and snapshot structure.

### Documentation

- Added `docs/maintenance-center.md` with current capabilities, safety boundaries, and later planned attachments.

## 2026-06-05 - Step 007 Web installer and updater

### Implemented

- Added a protected setup status page at `/settings/setup` behind `auth`, `verified`, and `password.confirm`.
- Added `InspectSetupStatus` and `SetupStatusReport` to inspect app key, HTTPS URL, database, migrations table, pending migrations, sync queue mode, restricted-hosting mode, and storage writability without running shell commands.
- Added English setup translations in `lang/en/setup.php`.
- Added setup navigation inside the settings layout.

### Testing

- Added setup status tests for route protection, password confirmation, rendering, and the status inspector.

### Documentation

- Added `docs/web-installer-updater.md` documenting the status-only web updater foundation and the no-public-installer policy.

## 2026-06-05 - Step 006 Restricted hosting web-only mode

### Implemented

- Added `config/hosting.php` and `App\Data\Hosting\WebProcessingProfile` to make chunked, resumable, web-triggered processing defaults explicit.
- Changed the default queue connection to `sync` so normal app usage does not imply a queue worker.
- Simplified `composer run dev` to Vite only because Laravel Herd serves the application locally.
- Removed the default console `inspire` command so there are no application console workflows.
- Updated `.env.example` for `https://ruflo.test`, sync queue defaults, and restricted-hosting processing knobs.

### Testing

- Added restricted-hosting tests that guard config defaults, queue/dev script assumptions, the empty console route, and the absence of `app/Jobs`.

### Documentation

- Added `docs/restricted-hosting.md` with the web-only processing contract and limitations.

## 2026-06-05 - Step 005 Tailwind CSS 4 and SCSS design layer

### Implemented

- Kept `resources/css/app.css` as the Tailwind CSS 4 and Flux UI entry using CSS-first `@theme` configuration.
- Added `resources/scss/app.scss` as a separate Vite entry for reusable tokens, accessibility helpers, shared surfaces, and print rules.
- Moved the welcome-page grid and veil styles out of inline Blade CSS and into the SCSS surface layer.
- Added the free `sass-embedded` compiler for Vite SCSS support.

### Testing

- Added `FrontendAssetPipelineTest` to guard the Tailwind/SCSS/Vite contract.

### Documentation

- Added `docs/frontend-design-system.md` with Tailwind 4, Flux, and SCSS usage rules.

## 2026-06-06 - Step 033 Overdue view

### Implemented

- Added a protected class-based Livewire Overdue page at `todos.overdue`
  (`/todos/overdue`) for active tasks past their due date.
- Added `TodoListQuery::overdueFor()` and `findOverdueFor()` so Overdue reads
  and actions stay owner-scoped and limited to active overdue tasks.
- Added a dashboard shortcut to the Overdue page beside the Today and full todo
  workspace links.
- Added translated Overdue page copy, empty-state copy, count labels, and
  filtered list action text.

### Testing

- Added `OverdueViewTest` for guest/unverified redirects, owner-only overdue
  rendering, active-only exclusions, translated empty state, overdue-only
  completion, route middleware, and URL contract.
- Re-ran adjacent Today, date, organization, private-view, project-detail,
  dashboard, domain, route-protection, and architecture coverage.

### Documentation

- Updated `docs/task-organization.md`, `docs/domain-readiness.md`, and
  `docs/todo-foundation.md` for the dedicated Overdue view and route contract.

## 2026-06-06 - Step 032 Today view

### Implemented

- Added a protected class-based Livewire Today page at `todos.today`
  (`/todos/today`) for active tasks due today.
- Added `TodoListQuery::todayFor()` and `findTodayFor()` so Today reads and
  actions stay owner-scoped and limited to active due-today tasks.
- Added a dashboard shortcut to the Today page while preserving the existing
  full todo workspace link.
- Added translated Today page copy, empty-state copy, count labels, and filtered
  list action text.

### Testing

- Added `TodayViewTest` for guest/unverified redirects, owner-only due-today
  rendering, active-only exclusions, translated empty state, due-today-only
  completion, route middleware, and URL contract.
- Re-ran adjacent date, organization, private-view, project-detail, dashboard,
  domain, route-protection, and architecture coverage.

### Documentation

- Updated `docs/task-organization.md`, `docs/domain-readiness.md`, and
  `docs/todo-foundation.md` for the dedicated Today view and route contract.

## 2026-06-06 - Step 031 Due dates and date logic

### Implemented

- Added `App\Rules\Todos\DueDate` for canonical `Y-m-d` due-date validation.
- Wired the due-date rule into the shared Livewire todo form used by create and
  edit flows.
- Hardened `TodoData::fromArray()` so direct callers normalize empty due dates
  to `null` and invalid provided dates raise translated validation errors.
- Added `Todo::isUpcoming()` so the model has explicit helper parity with the
  today/overdue/upcoming query scopes.

### Testing

- Added `TaskDueDateTest` for strict date parsing, Livewire create/edit
  rejection, direct DTO bypass protection, app-timezone buckets, active-only
  exclusions, owner scoping, and due-filter tab sanitization.
- Re-ran adjacent todo organization, creation, editing, dashboard, factory,
  seeder, validation-rule, private-view, project-detail, and architecture
  coverage.

### Documentation

- Updated `docs/task-organization.md`, `docs/validation-rules.md`, and
  `docs/todo-foundation.md` with the date-only validation and timezone
  contract.

## 2026-06-06 - Step 030 Priorities

### Implemented

- Tightened task priority validation to Laravel's enum rule for Livewire
  create/edit forms.
- Hardened `TodoData::fromArray()` so direct callers may omit priority and get
  Normal, but cannot submit an invalid priority that silently falls back.
- Centralized priority sorting through `App\Enums\Priority::sortCaseSql()` so
  the query sort weights stay aligned with enum labels, badge colors, and
  weights.

### Testing

- Added `TaskPriorityTest` for enum labels/colors/weights, invalid Livewire
  create/edit priorities, direct DTO/action bypass behavior, owner-scoped
  filtering, and enum-owned sort SQL.
- Re-ran the adjacent todo organization, creation, editing, private view,
  project detail, factory, seeder, and architecture tests.

### Documentation

- Updated `docs/task-organization.md` and `docs/todo-foundation.md` with the
  tightened priority validation, sorting, and testing contract.

## 2026-06-05 - Step 4 stabilization

### Implemented

- Added project rename support in the management modal with owner-scoped lookup,
  authorization, validation attributes, and tests.
- Added bulk unarchive and bulk move actions. Both re-scope selected IDs to the
  current user; bulk move validates the target project belongs to the user and
  is active.
- Added due-date "with/without" filters, project-name sorting, safer validation
  for selected IDs, and empty-state copy driven by the active filter context.

### Testing

- Full suite: 122 passed.

## 2026-06-05 - Step 4 Task organization, filters, search, sorting, bulk actions

### Implemented

- Added **projects** (owner-scoped task grouping): `projects` table, `todos.project_id` (nullable, `nullOnDelete` so deleting a project keeps its tasks as "No project"), `ProjectPolicy`, and create/rename/archive/restore/delete actions. Projects archive (reversible) distinctly from delete.
- Added **tags**: `tags` table + `tag_todo` pivot, per-user unique normalized (squished, lower-cased) names via `firstOrCreate`, `TagPolicy`, create/delete actions. Deleting a tag keeps the tasks.
- Added **priority** (`App\Enums\Priority`: low/normal/high/urgent with labels, colors, sort weights) and **due dates** on tasks, with active-only date buckets (today/overdue/upcoming) as model scopes + `isOverdue()`/`isDueToday()` helpers. Completed/archived tasks are never overdue.
- Centralized **search/filter/sort** in `TodoListQuery::filtered()` with a sanitized `TodoFilters` value object: title search with escaped LIKE wildcards (`ESCAPE` clause), filters by lifecycle/project/"none"/tag/priority/due, allow-listed sorting (created/due/priority/title, asc/desc) safe against `?sort=` injection, and 15/page pagination.
- Added **bulk actions** (complete/archive/delete) that re-scope the selected ids to the user's own tasks inside the query, so foreign ids are silently excluded and bulk complete/archive respect lifecycle state.
- Kept `project_id` out of `#[Fillable]`; `CreateTodo`/`UpdateTodo` set it directly only after re-scoping project and tag ids to the owner (`ResolvesTodoOrganization`).

### UI

- Rebuilt the task workspace: summary stats (active/overdue/completed/archived), filter toolbar, bulk toolbar, per-row priority/due/project/tag badges, pagination, an edit modal with all fields, and a "Manage" modal for projects and tags. Added reusable `x-ui.stat`. All copy in `lang/en/todos.php`; pickers only list the user's own resources.

### Testing

- Added `ProjectTest` (7), `TagTest` (6), `TodoOrganizationTest` (18): creation with organization data, ownership-safe project/tag assignment (foreign refs dropped), per-field validation, project/tag/priority/search filtering with cross-user isolation, LIKE-wildcard literal handling, due-date buckets + overdue summary, priority sorting, sort-injection fallback, and bulk actions that can't touch another user's tasks.
- Enriched the seeder: two users each with projects (incl. archived), tags, and tasks across all states (today/overdue/upcoming/high-priority/completed/archived).
- Full suite: 113 passed (was 82).

### Documentation

- Added `docs/task-organization.md` covering projects, tags, priority, due-date buckets, the timezone assumption, search/filter/sort safety, bulk-action scoping, performance/indexes, and what Step 5 builds next.

### Intentionally not implemented

- Manual ordering, saved filter views, sub-projects, project detail pages, reminders, recurring tasks, dashboard, collaboration.

## 2026-06-05 - Step 3 Core task lifecycle

### Implemented

- Defined an explicit task state machine: active ⇄ completed, active/completed → archived → (unarchive to prior bucket), and any non-deleted → trashed (soft delete). States are derived from `is_completed`, `archived_at`, and `deleted_at`; archived takes precedence over completion.
- Added `archived_at` to `todos` with a `(user_id, archived_at)` index; archive is distinct from both completion and deletion.
- Added `App\Enums\TodoStatus` (Active/Completed/Archived) with translatable labels and badge colors, and model helpers/scopes (`status()`, `isActive()`, `isArchived()`, `scopeActive/Completed/Archived`).
- Added one action per transition: `UpdateTodo`, `ArchiveTodo`, `UnarchiveTodo`, later refined to explicit `CompleteTodo` and `ReopenTodo`; hardened completed cleanup to respect archive state. `archived_at` is set directly (system-controlled, never mass-assignable).
- Added `InvalidTodoTransition` so completing or editing an archived task fails safely as a translatable warning, never a 500 or a leak.
- Extended `TodoListQuery` with `forStatus()` buckets and a three-way `summaryFor()` (active/completed/archived counts) in a single scoped query.
- Added domain events `TodoUpdated`, `TodoArchived`, `TodoUnarchived` (alongside the existing create/complete/reopen/delete/clear events) for future activity history and reminders.

### UI

- Rebuilt the task list around a lifecycle segmented control (Flux Free has no tabs component) with live per-bucket counts, a create form on the Active tab, state-aware row actions in a dropdown (edit/archive on non-archived, unarchive on archived, delete always with `wire:confirm`), an edit modal, a reusable `x-ui.status-badge`, and per-tab empty states.
- All new copy added to `lang/en/todos.php`; nothing user-facing is hardcoded.

### Testing

- Added `TodoLifecycleTest` (18 tests): status derivation, per-bucket listing and summary, archive/unarchive (completion preserved), archived-completion rejection, edit + edit validation, archived-edit refusal, soft delete, clear-completed isolation from archive, invalid-tab fallback, and cross-user denial across every lifecycle action (data-driven over complete/reopen/edit/archive/unarchive/delete).
- Full suite: 82 passed (was 64).

### Documentation

- Added `docs/task-lifecycle.md`: states, allowed/rejected transitions, where each concern lives, events, validation, UI states, and what Step 4 builds next.

### Intentionally not implemented

- Projects/lists, tags, priorities, due dates, search, non-lifecycle filters, sorting, pagination, bulk actions, a trash-restore UI, reminders, recurring tasks, dashboard, and collaboration.

## 2026-06-05 - Step 2 Private workspace, ownership & authorization

### Inspected

- Confirmed Fortify web authentication (login, registration, verification, 2FA, passkeys); the authenticated user drives every scope.
- Confirmed private routes are grouped behind `['auth', 'verified']` in `routes/web.php`; no public route touches private data.
- Confirmed there are no roles, gates, teams, workspaces, or admin/multi-tenant logic — the owning `User` is the workspace boundary.
- Confirmed `TodoPolicy` resolves for the `Todo` model and Step 1 cross-user isolation tests still pass.

### Prepared

- Added `App\Models\Concerns\BelongsToUser` as the single source of truth for ownership: `scopeOwnedBy()`, `isOwnedBy()`, and the `user()` relationship. Future todo resources reuse it for identical behavior.
- Bound the policy explicitly with `#[UsePolicy(TodoPolicy::class)]` on `Todo` instead of relying on naming-convention auto-discovery.
- Refactored `TodoListQuery` so every list, lookup, and counter flows through `ownedBy()`; client IDs resolve to not-found when foreign, so existence never leaks.
- Hardened mass assignment: `user_id` stays out of `#[Fillable]`; a test proves a submitted `user_id` is ignored.
- Seeded two isolated user workspaces so private isolation can be exercised by hand and by tests.

### Testing

- Added `TodoOwnershipTest`: policy resolution, owner-allow / non-owner-deny for view/update/complete/delete/unarchive, not-found (404) leakage behavior, `forceDelete` disabled, mass-assignment refusal, owner-scoped query + counters, and class-level abilities.
- Full suite: 62 passed (was 51).

### Documentation

- Added `docs/authorization.md` specifying the core invariant, workspace boundary, single-source-of-truth locations, query scoping, route protection, no-leak error behavior, preparation for dashboard/search/filters/bulk/activity/notifications/collaboration, and testing requirements.

### Intentionally not implemented

- Task lifecycle screens, edit/archive/unarchive actions, dashboard, search, filters, bulk actions, reminders, collaboration, roles, and any `Workspace` model (the `User` remains the boundary for now).

## 2026-06-05 - Step 1 Todo foundation

### Analyzed

- Verified the app is a Laravel 13.14 Livewire starter-style project with Fortify authentication, Flux UI, Tailwind CSS v4, Vite, Pest, Pint, and SQLite.
- Confirmed the authenticated shell, settings routes, Fortify views, package scripts, test bootstrap, current database schema, existing reusable components, and current Todo implementation.
- Confirmed the full Pest suite passed before the Step 1 foundation edits.
- Confirmed this directory is a Git repository on `main`; Step 1 foundation changes are committed after verification.

### Prepared

- Added explicit Todo domain boundaries for actions, query logic, Livewire form validation, data transfer, authorization, and events.
- Added `TodoPolicy` with owner-only lifecycle checks and permanent deletion disabled by default.
- Added soft deletes to `todos` so delete behavior does not permanently remove user work.
- Added `TodoListQuery` for owner-scoped lists and aggregate counts.
- Added domain events for Todo creation, completion/reopening, deletion, and clearing completed todos.
- Added reusable `x-ui.page-header` and `x-ui.empty-state` components for future Todo screens.
- Added `lang/en/todos.php` so Todo UI text and messages are translation-ready.

### Testing

- Enabled `RefreshDatabase` for Feature tests in the Pest bootstrap.
- Added Todo behavior tests for authentication, owner-scoped viewing, creation, validation, completion/reopening, soft deletion, completed cleanup, and cross-user mutation attempts.
- Added Todo architecture tests to guard thin Livewire components, shared UI components, translation keys, and required documentation.

### Documentation

- Added `docs/todo-foundation.md` with analysis findings, architecture rules, ownership principles, validation rules, lifecycle rules, UI rules, translation rules, testing rules, run commands, and Step 2 direction.
- Added this changelog so future agents can see what Step 1 prepared and what remains intentionally unbuilt.

### Intentionally not implemented

- Projects, tags, priorities, due dates, reminders, dashboards, search, filters, bulk edit, activity history, notifications, collaboration, workspaces, roles, import, and export.
- Permanent deletion flows.
- Admin panel logic.
