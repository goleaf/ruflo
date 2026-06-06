# Protected Maintenance Center

## Step 008 Scope

Step 008 adds a protected maintenance center at `/settings/maintenance`.

The route is authenticated, verified, password-confirmed, and admin-gated through the `access-maintenance-center` gate. There is no public maintenance route.

The current admin boundary is `users.is_admin`. The primary seeded demo user (`test@example.com`) is an admin in local/testing/demo data; the secondary seeded demo user (`second@example.com`) is not an admin and is used for denial coverage.

## Current Capabilities

- Shows setup health checks from the protected setup inspector.
- Shows the configured web-processing profile: manual Livewire chunk engine,
  chunk size, request time window, retry cooldown, resume flag, and detail row
  limit.
- Shows runtime state for cache store, session driver, queue connection, compiled view count, and storage writability.
- Provides a web-safe compiled-view cleanup action.
- Provides a web-safe application cache flush action.

## Safety Boundaries

The current center does not:

- run migrations,
- call Artisan,
- spawn shell commands,
- run queue workers,
- delete user data,
- reset demo data.

Those actions need the later generic processing engine or dedicated planned features.

## Later Attachments

- Step 053: generic manual web processing engine with chunking, progress, retry, and resume.
- Step 077: storage and cleanup center.
- Step 087: demo reset and sample data tools.

## 2026-06-06 Step 053 Update

The maintenance snapshot now exposes the reusable manual web-processing engine
as `manual_livewire_chunks`, plus its detail row limit. This is read-only
runtime visibility for admins; processing still happens through feature-owned
Livewire actions so private data remains behind the relevant owner policies and
queries.

## 2026-06-06 Recheck

Step 008 was rechecked from the root prompt pack and `steps/step-008-protected-maintenance-center.md`.

Confirmed and updated:

- Added `users.is_admin` as the narrow admin marker for maintenance access.
- Registered the `access-maintenance-center` gate.
- Added route and Livewire action authorization for the maintenance center.
- Kept dangerous actions web-triggered and bounded to cache and compiled-view cleanup.
- Kept all maintenance copy in `lang/en/maintenance.php`.
- Added factory and seeder coverage for admin and non-admin demo users.
- Applied the local migration and reseeded demo data so `https://ruflo.test/settings/maintenance` is usable by the primary demo admin.
