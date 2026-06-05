# Stack Readiness

Step 002 verifies that the application is aligned with the current Laravel 13, Livewire 4, Flux 2, Tailwind CSS 4, and restricted-hosting baseline.

## 2026-06-06 Recheck

- Laravel Boost reports PHP CLI 8.4, Laravel 13.14.0, Livewire 4.3.1, Flux UI Free 2.14.1, Fortify 1.37.2, Pest 4.7.2, PHPUnit 12.5.28, Pint 1.29.1, and Tailwind CSS 4.3.0.
- `composer.json` requires Laravel 13-compatible packages and remains valid under `composer validate --strict --no-interaction`.
- `composer install --dry-run --no-interaction` reports the lock file is installable on the current platform with no pending dependency changes.
- Full-page Livewire routes use `Route::livewire()` for application and settings pages.
- No published `config/livewire.php` drift exists in this checkout; Livewire 4 defaults are used with class-based components where the app already uses classes.
- `vite.config.js` builds Tailwind CSS, the clean SCSS layer, app JavaScript, passkey JavaScript, and bundled fonts through the Laravel Vite plugin.
- `npm run build` succeeds and leaves the worktree clean.

## Restricted Hosting

Normal application usage must not depend on terminal access, queue workers, cron, supervisors, or shell scripts. The current stack baseline keeps the production queue default at `sync`, serves local development through Herd at `https://ruflo.test`, and reserves command-line setup/build/test commands for development and deployment workflows only.
