# TODO Master Decisions

Record important architecture/product decisions here.

| Date | Area | Decision | Reason |
|---|---|---|---|
| 2026-06-05 | Prompt location | Use the root `MASTER_PROMPT.md` and `steps/` files because `docs/todo-master-plan/MASTER_PROMPT.md` is not present in this checkout. | The prompt pack exists at the repository root and contains the complete 100-step sequence. |
| 2026-06-05 | Runtime | Code against the installed Laravel Boost runtime: Laravel 13.14, Livewire 4.3, Flux 2.14, Tailwind 4.3, Pest 4.7, PHP CLI 8.4. | Local installed versions are authoritative for tests and generated code. |
| 2026-06-05 | Step continuation | Treat existing commits `b69ac76`, `e53b67c`, `2149412`, and `b461fae` as completed Step 001-004 baseline work. | Docs, changelog, tests, and git history already describe and verify the foundation, stack, Livewire/Flux, and no-Volt baseline. |
| 2026-06-05 | Worktree recovery | Do not commit placeholder reminder/notification files until they are implemented and tested. | The interrupted staged batch contained generated skeletons that are not stable product behavior. |
| 2026-06-05 | Frontend assets | Keep Tailwind CSS 4 in `resources/css/app.css` and add SCSS as a separate `resources/scss/app.scss` Vite entry. | This preserves Tailwind/Flux conventions while allowing clean shared tokens, accessibility helpers, reusable surface effects, and print styles. |
| 2026-06-05 | Restricted hosting | Default normal runtime to `QUEUE_CONNECTION=sync` and web-triggered chunked processing. | Shared hosting cannot assume workers, cron, supervisors, shell access, or Artisan access for normal usage. |
| 2026-06-05 | Web setup | Provide a protected status-only setup page before any web updater execution controls. | This gives owners deployment visibility without exposing a public installer or risky migration runner. |
| 2026-06-05 | Maintenance center | Implement only bounded cache/view cleanup actions before the generic processing engine exists. | Broader retry/resume processors, demo reset, and storage cleanup are already planned later and should not be improvised unsafely. |
| 2026-06-05 | Domain readiness | Treat `https://ruflo.test` as the configured root for tracked defaults, tests, and local Herd runtime. | Generated redirects, signed links, storage URLs, and future invite/export/download links need a stable HTTPS host instead of implicit localhost or HTTP fallbacks. |
| 2026-06-05 | Demo login | Render demo credentials only from a safe-environment, config-backed, database-confirmed catalog and submit quick logins through Fortify. | The login page needs local/demo usability without bypassing auth protections or exposing known credentials in production-like environments. |
| 2026-06-05 | Factory inventory | Scope Step 011 to committed models only: User, Project, Tag, and Todo. | The reminder files in the working tree are untracked interrupted scaffold and should not define the current production model inventory until their planned step implements them. |
| 2026-06-05 | Seeder inventory | Seed only committed models and make the current demo seeders idempotent. | The app needs immediate local/demo usability without duplicating records on reruns or creating known-password users in production-like environments. |
| 2026-06-05 | Demo login identifier | Show the seeded demo users' email addresses as the login identifier instead of adding usernames. | The current users table and Fortify configuration authenticate by email only, so introducing a username field would expand scope beyond the demo panel. |
| 2026-06-05 | Request validation | Treat auth Form Request classes as the canonical rule source for Fortify registration and password reset actions. | Fortify passes arrays to action contracts, so sharing request-class rules avoids inline action validation while preserving Fortify's normal flow. |
| 2026-06-05 | Livewire validation | Keep Livewire-only task/profile validation in Livewire form objects or components unless a separate HTTP route/controller exists. | Livewire form objects are the correct boundary for component state and avoid forcing HTTP Form Requests into non-HTTP interactions. |
