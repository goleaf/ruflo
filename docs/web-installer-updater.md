# Web Installer And Updater

## Step 007 Scope

Step 007 adds a protected setup status surface. It does not run migrations or mutate deployment state yet.

## Route

- Route: `/settings/setup`
- Name: `setup.status`
- Middleware: `auth`, `verified`, `password.confirm`
- Public installer route: none

The page is intentionally inside authenticated settings so setup information is not exposed to guests.

## Status Checks

`App\Actions\Setup\InspectSetupStatus` reports:

- application key configured,
- application URL uses HTTPS,
- database connection available,
- migrations table present,
- pending migration count,
- queue connection is `sync`,
- restricted hosting mode enabled,
- storage/framework is writable.

The result is rendered by `App\Livewire\Settings\SetupStatus` and translated through `lang/en/setup.php`.

## Migration Strategy

Restricted hosting may not have terminal access. The app therefore needs a later protected web updater to run bounded setup work through authenticated Livewire actions.

For now, this page is status-only:

- no public setup token,
- no public installer,
- no automatic migration execution,
- no shell calls,
- no Artisan dependency for normal browser usage.

Step 008 will add the broader maintenance center. Step 053 will add the reusable manual web processing engine that updater actions should use.
