# Domain Readiness

RuFlo is developed and tested against the secured Herd domain:

```text
https://ruflo.test
```

## Source Of Truth

- `.env.example` sets `APP_URL=https://ruflo.test`.
- `phpunit.xml` sets the same `APP_URL` for test runs.
- `config/app.php`, `config/filesystems.php`, and `config/mail.php` default to `https://ruflo.test` if no environment value is present.
- `App\Providers\AppServiceProvider` forces Laravel URL generation to the configured `app.url` root and forces the HTTPS scheme when the configured root is HTTPS.

This keeps links stable for normal web requests, Fortify redirects, signed URLs, public storage URLs, and future web-triggered workflows that may run without shell access.

## Current Coverage

The current application surface covers these URL consumers:

- Public home route at `/`.
- Authenticated dashboard route at `/dashboard`.
- Authenticated goals route at `/goals`.
- Authenticated habits route at `/habits`.
- Authenticated notification center route at `/notifications`.
- Authenticated task board route at `/todos/board`.
- Authenticated task calendar route at `/todos/calendar`.
- Authenticated task templates route at `/todos/templates`.
- Authenticated task Inbox route at `/todos/inbox`.
- Authenticated task focus route at `/todos/focus`.
- Authenticated task time tracking route at `/todos/time`.
- Authenticated blocked tasks route at `/todos/blocked`.
- Authenticated cleanup smart views route at `/todos/cleanup`.
- Authenticated browser-triggered automation rules route at `/todos/automations`.
- Authenticated browser-triggered reminders route at `/todos/reminders`.
- Authenticated todo route at `/todos`.
- Authenticated Today route at `/todos/today`.
- Authenticated Overdue route at `/todos/overdue`.
- Authenticated Upcoming route at `/todos/upcoming`.
- Authenticated task detail routes at `/todos/{todo}`.
- Protected setup route at `/settings/setup`.
- Protected maintenance route at `/settings/maintenance`.
- Fortify login, registration, password confirmation, and verification routes.
- Public storage URLs generated from the configured public disk.

## Future Feature Rules

Later invite, export, notification, and protected-download steps must reuse this domain contract:

- Generate internal links with named routes and `route()`.
- Generate temporary access links with `URL::temporarySignedRoute()`.
- Generate public storage links with the configured disk URL.
- Do not hardcode `localhost`, `http://`, or an alternate local domain in application code.
- Keep link-only invites independent of email delivery.
- Keep export, cleanup, automation, and processing workflows web-triggered,
  chunked, retryable, and resumable when the work can exceed a normal request.

## Restricted Hosting

Domain readiness does not add any worker, cron, supervisor, shell, or paid service dependency. Runtime health remains visible through the protected setup and maintenance pages.

## 2026-06-06 Recheck

Step 009 was rechecked from the root prompt pack and `steps/step-009-domain-and-ruflo-test-readiness.md`.

Confirmed:

- Boost resolves the root URL, login route, and maintenance route to `https://ruflo.test`.
- Runtime `app.url` resolves to `https://ruflo.test`.
- Public storage URLs resolve under `https://ruflo.test/storage`.
- Runtime environment remains `local` and queue default remains `sync`.
- Settings routes include setup and admin-gated maintenance surfaces.
- Focused domain, auth, setup, and maintenance tests pass.
- Hardcoded-host scanning found no alternate internal localhost or HTTP `ruflo.test` URLs; remaining hits are external source/docs/demo links, schema references, AWS example config, and HTTPS guard code.
- Browser logs only showed an old Vite reconnect message, not a current domain/runtime error.

## 2026-06-06 Step 052 Update

Step 052 adds the protected `todos.automations` route at
`https://ruflo.test/todos/automations`. Boost URL generation resolves the route
to that HTTPS Herd domain, and the route collection shows `web`, `auth`, and
`verified` middleware.

## 2026-06-06 Step 053 Update

Step 053 adds no new public route. The reusable manual web-processing engine is
triggered by feature-owned Livewire actions such as `/todos/automations`, and
its runtime profile is visible on the existing protected maintenance route at
`https://ruflo.test/settings/maintenance`.

## 2026-06-06 Step 054 Update

Step 054 adds the protected `todos.reminders` route at
`https://ruflo.test/todos/reminders`. Reminder notification payloads use named
routes for task action links, so links stay under the configured
`https://ruflo.test` root. Known private task links are also pre-checked before
the notification center renders an Open action.

## 2026-06-06 Step 055 Update

Step 055 adds the protected `notifications.inbox` route at
`https://ruflo.test/notifications`. The notification center only renders
relative links or same-host `https://ruflo.test` links after rejecting external,
protocol-relative, and unsupported-scheme notification payload URLs. Known task
links are pre-checked against the current user's private task scope before an
Open action is rendered.
