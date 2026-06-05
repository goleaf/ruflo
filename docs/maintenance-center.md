# Protected Maintenance Center

## Step 008 Scope

Step 008 adds a protected maintenance center at `/settings/maintenance`.

The route is authenticated, verified, and password-confirmed. There is no public maintenance route.

## Current Capabilities

- Shows setup health checks from the protected setup inspector.
- Shows the configured web-processing profile: chunk size, request time window, retry cooldown, and resume flag.
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
