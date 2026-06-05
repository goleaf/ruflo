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
- Authenticated todo route at `/todos`.
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
- Keep export and cleanup workflows web-triggered, chunked, retryable, and resumable when the work can exceed a normal request.

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
