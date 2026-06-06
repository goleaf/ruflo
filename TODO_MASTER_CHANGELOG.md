# TODO Master Changelog

Record changes after every step.

## Unreleased

- Copied root progress files from `progress-templates/`.
- Verified existing Step 001, Step 002, Step 003, and Step 004 implementation commits and updated master progress to continue from Step 005.
- Recorded the root prompt-pack path adjustment because `docs/todo-master-plan/MASTER_PROMPT.md` is absent in this checkout.
- Confirmed the full test suite passes before Step 05 work.
- Normalized root progress and decisions to the current 100-step prompt pack and set the next executable step to Step 005.
- Completed Step 005 by adding a clean SCSS Vite entry for tokens, accessibility helpers, surfaces, and print styles while keeping Tailwind CSS 4 as the primary styling layer.
- Completed Step 006 by adding restricted-hosting config defaults, a web-processing profile, sync queue defaults, a Vite-only local dev script, docs, and drift tests.
- Completed Step 007 by adding a protected setup status page, setup status inspector, translations, docs, and route protection tests.
- Completed Step 008 by adding a protected maintenance center with setup health, runtime/profile status, safe cache flush, safe compiled-view cleanup, docs, translations, and tests.
- Completed Step 009 by aligning tracked defaults, test runtime, and local Herd runtime with `https://ruflo.test`, forcing configured URL generation, documenting future link rules, and adding URL contract tests.
- Completed Step 010 by adding an environment-gated seeded demo login panel, Fortify quick-login forms, auth translations, safe demo seeding rules, docs, and tests.
- Completed Step 011 by expanding all tracked model factories with auth/demo, lifecycle, date, priority, ownership, project, tag, and edge-case states plus coverage tests.
- Completed Step 012 by splitting safe demo user seeding, making workspace seeders idempotent, seeding complete current-model demo data, and adding seeder coverage tests.
- Completed Step 013 by re-verifying the demo login panel contract, asserting rendered roles/descriptions, documenting the email-only login identifier, and keeping quick login inside Fortify.
- Completed Step 014 by adding auth Form Request classes, wiring Fortify actions to those canonical rules, translating auth validation labels/messages, and documenting the request-validation boundary.
- Completed Step 015 by adding reusable todo ownership validation rules, applying them to task organization and bulk actions, translating failures, and documenting current versus future rule domains.
- Completed Step 016 by moving remaining auth/settings/navigation/dashboard/welcome visible copy and Livewire action messages into English language files, adding translation-key page titles, documenting localization guardrails, expanding 001-100 progress tracking, and adding localization regression tests.
- Rechecked Step 001 from the start of the plan, confirming package versions, routes, `https://ruflo.test`, sync queue defaults, no Volt usage, project/list ownership behavior, docs, and focused foundation tests.
- Rechecked Step 002 by confirming Laravel 13/Livewire 4/Flux 2/Tailwind 4 package versions, Laravel 13 bootstrap/routing conventions, Composer validity, install dry-run, Vite build output, and stack-focused tests.
- Rechecked Step 003 by confirming class-based Livewire component/view inventory, `Route::livewire()` routes, Flux free component usage, absence of deprecated Flux aliases/manual Livewire asset directives, browser logs, docs, and focused Livewire/Flux tests.
- Rechecked Step 004 by confirming Volt is not installed, no Volt commands/files/source imports exist, class-based Livewire remains the convention, and focused Livewire todo/project/tag/settings tests pass.
- Rechecked Step 005 by confirming the Tailwind CSS 4 and SCSS Vite asset split, runtime SCSS partial scope, absence of deprecated Tailwind v3 syntax in app sources, frontend asset pipeline test coverage, and successful Vite production build.
- Rechecked Step 006 by confirming sync queue defaults, restricted-hosting config/profile values, absence of app jobs and console workflows, Vite-only local dev script, setup/maintenance exposure, docs, composer validation, and focused hosting tests.
- Rechecked Step 007 by confirming the protected setup-status route, class-based Livewire and Flux setup UI, translated setup copy, absence of public installer routes, and by hardening database diagnostics so raw exception details cannot render or appear in Livewire public state.
- Rechecked Step 008 by adding the missing admin boundary for the maintenance center, gating the route and Livewire actions, adding `users.is_admin`, updating factory and demo seeder admin states, applying the local migration and seed, and verifying admin/non-admin behavior.
- Rechecked Step 009 by confirming `https://ruflo.test` URL generation, app/storage config, settings routes, hardcoded-host drift scans, browser logs, and focused domain/auth/setup/maintenance tests.
- Rechecked Step 010 by confirming Fortify login docs and wiring, safe demo panel gates, seeded admin/normal demo roles, production/disabled hiding, normal Fortify quick-login behavior, local demo password hashes, translations, and focused auth coverage.
- Rechecked Step 011 by confirming the concrete model/factory inventory, adding coverage for the tracked `Reminder` placeholder factory, documenting deferred reminder states, and passing focused factory plus full-suite tests.
- Rechecked Step 012 by confirming safe environment-gated demo seeding, idempotent workspace seeding, local seeded demo-user counts, placeholder reminder no-op behavior, login URL readiness, docs, and focused plus full-suite tests.
- Rechecked Step 013 by confirming Fortify login route/config behavior, Flux demo panel rendering, safe environment gates, both seeded demo accounts logging in through Fortify, guest redirects from private routes, real local hashes, browser logs, docs, and full-suite tests.
- Rechecked Step 014 by confirming the current request-driven input inventory, Fortify Form Request canonical rule sources, Livewire-only validation boundary, translated request-contract tests, docs, and focused plus full-suite validation tests.
- Rechecked Step 015 by confirming the custom validation rule inventory, removing the empty reminder placeholder rule, adding architecture coverage for implemented translated rule failures, documenting deferred future-domain rules, and passing focused plus full-suite tests.
- Rechecked Step 016 by confirming the English language-file inventory, literal translation API scanner, localized landing pages, static translation-key existence coverage, restricted-hosting localization behavior, docs, and focused plus full-suite tests.
- Rechecked Step 017 by keeping the owning user as the private workspace boundary, adding owner-scoped dashboard counters through `DailySummaryQuery`, guarding todo project/tag hydration against malformed cross-user links, documenting the contract, and adding private-workspace regression coverage.
- Completed Step 018 by locking server-assigned todo edit IDs, keeping edit-form tag hydration on the owner-scoped query result, returning empty results for tampered foreign/archived project/tag filters, documenting the query-scoping contract, and adding ownership query-scoping regression tests.
- Completed Step 019 by adding an explicit todo reopen policy ability, authorizing complete versus reopen based on task state, explicitly binding the reminder deny-all policy, standardizing owner policy checks through `isOwnedBy()`, documenting deferred membership roles, and adding authorization policy matrix tests.
- Completed Step 020 by enabling Laravel's `MustVerifyEmail` contract on `User`, adding guest/unverified/password-confirmation/maintenance route-protection coverage, guarding protected route middleware expectations, and confirming the demo login panel never exposes stored password hashes.
- Completed Step 021 by hardening `CreateTodo` title normalization, removing completion state from todo mass assignment, preserving lifecycle updates inside explicit actions, adding create-form error placement, and adding core creation regression tests.
- Completed Step 022 by adding a private class-based Livewire task detail page, resolving detail records through the owner-scoped todo query, linking list rows only to rendered owner tasks, translating detail metadata, and adding private list/detail regression tests.
- Completed Step 023 by hardening `UpdateTodo` title normalization, preserving edit/lifecycle separation, adding edit-modal field error placement, documenting activity-readiness events, and adding task editing regression tests.
- Completed Step 024 by replacing the generic completion toggle boundary with explicit complete/reopen actions, events, Livewire methods, translated accessibility labels, and bulk completion that reuses the complete transition.
- Completed Step 025 by making task archive reversal explicit as unarchive, replacing task bulk restore with `BulkUnarchiveTodos`, routing bulk archive/unarchive through single-task transitions, preserving completion state, and adding archive/unarchive regression tests.
- Completed Step 026 by adding a private Trash tab, restore-from-trash actions/events, owner-scoped trash lookups, selected deleted-task validation, dashboard trash counts, demo trash seed data, and eventful bulk delete/restore behavior while keeping force delete disabled.
- Completed Step 027 by adding `TodoTransition`, centralizing accepted lifecycle source states and target buckets in `TodoLifecycleStateMachine`, guarding all transition actions through it, language-backing invalid transition messages, and adding state-machine regression tests.
- Completed Step 028 by adding a private owner-scoped project detail Livewire page and protected route, linking project badges to it, preserving archived-project readability without reintroducing archived projects into active pickers, and adding project-detail privacy tests.
- Completed Step 029 by adding normalized tag-name validation, hardening direct tag creation against empty normalized labels, linking tag badges to the owner-scoped tag filter, and expanding tag isolation/assignment/filter tests.
- Completed Step 030 by tightening priority validation with Laravel enum rules, rejecting invalid direct DTO priority data, centralizing priority sort SQL on the enum weights, and adding priority validation/filter/sort regression tests.
