# Authentication And Login UX

RuFlo uses Laravel Fortify for the authentication backend. The login page remains a normal HTML form that posts to Fortify's `login.store` route with CSRF protection, email, password, and optional remember-me fields.

## Demo Login Panel

The login page can show seeded demo users only when all of these are true:

- `config('demo.login_panel.enabled')` is true.
- `config('app.env')` is one of `local`, `testing`, or `demo`.
- The configured demo users exist in the database.

The panel is configured in `config/demo.php`. The current seeded demo users are:

- `test@example.com` with password `password`.
- `second@example.com` with password `password`.

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
