# Authentication And Login UX

RuFlo uses Laravel Fortify for the authentication backend. The login page remains a normal HTML form that posts to Fortify's `login.store` route with CSRF protection, email, password, and optional remember-me fields.

## Demo Login Panel

The login page can show seeded demo users only when all of these are true:

- `config('demo.login_panel.enabled')` is true.
- `config('app.env')` is one of `local`, `testing`, or `demo`.
- The configured demo users exist in the database.

The panel is configured in `config/demo.php`. The current seeded demo users are:

- `test@example.com` with password `password` and local maintenance admin access.
- `second@example.com` with password `password` and normal user access.

The current authentication model uses email as the login identifier. The users table does not include a username column, so the panel displays each seeded user's email address, role, and description rather than introducing a separate username concept.

Quick login buttons do not bypass authentication. Each button submits the configured email and password to Fortify's normal POST `/login` endpoint, so throttling, sessions, redirects, and two-factor challenge behavior remain in the existing auth pipeline.

## Production Safety

Known demo credentials are never rendered outside the safe environments listed above. `DatabaseSeeder` also creates the known demo users only in those safe environments so production-like seeding does not create known-password accounts.

Production deployments should use:

```text
APP_ENV=production
RUFLO_DEMO_LOGIN_PANEL=false
```

The environment gate is the primary safety boundary. A production deployment misconfigured as `local`, `testing`, or `demo` can expose the panel.

## Localization

Login-page copy added in Step 010 lives in `lang/en/auth.php`. New visible login text should continue to use translation keys instead of inline fallback strings.

## 2026-06-06 Rechecks

Step 010 was rechecked from the root prompt pack and `steps/step-010-authentication-and-login-ux.md`.

Confirmed and updated:

- The login page still posts to Fortify's `login.store` route.
- Demo users render only when enabled, in a safe environment, and present in the database.
- Demo quick-login buttons submit the configured email and fixed demo password through Fortify; there is no production bypass.
- Demo-user tests now use the same factory states as the seeder.
- The primary demo user is shown as the admin workspace and the secondary demo user remains a normal isolation workspace.

Step 013 was rechecked from `steps/step-013-demo-users-and-login-panel.md`.

Confirmed and updated:

- Fortify still owns the login GET and POST routes, with `email` as the configured username field and `/dashboard` as the authenticated home path.
- The demo panel uses Flux UI elements and normal POST forms; it does not use Livewire state or a custom login bypass.
- Both configured seeded demo users authenticate through Fortify with the fixed local/demo password.
- Private dashboard, todo, and profile settings routes redirect guests to the login route.
- The real local database still has `test@example.com` as the admin demo user and `second@example.com` as a normal demo user.
