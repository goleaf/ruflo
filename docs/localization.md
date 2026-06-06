# Localization

Step 016 keeps visible application copy in English language files instead of hardcoded Blade, Livewire, or action-message strings.

## Current language files

- `lang/en/auth.php` contains auth labels, placeholders, Fortify screen copy, passkey copy, and safe demo-login panel text.
- `lang/en/navigation.php` contains shared dashboard/sidebar/header menu labels.
- `lang/en/settings.php` contains profile, appearance, security, two-factor, recovery-code, passkey, and delete-account copy.
- `lang/en/dashboard.php` contains authenticated RuFlo dashboard copy.
- `lang/en/welcome.php` contains public landing page copy.
- `lang/en/automation.php` contains browser-triggered automation rule copy,
  validation messages, run statuses, and action messages.
- `lang/en/reminders.php` contains reminder page, validation, processing,
  preference, status, and notification copy.
- `lang/en/maintenance.php` contains the protected maintenance-center copy,
  including Step 053 manual web-processing engine labels.
- Existing domain files such as `lang/en/todos.php` and `lang/en/setup.php`
  remain the source for their feature surfaces.

## Guardrails

- New visible text should be added to the closest existing English language file before it is rendered.
- Livewire `#[Title]` values should use translation keys when a matching language line exists.
- `resources/views/partials/head.blade.php` resolves title keys through Laravel localization before rendering the browser title.
- `tests/Feature/LocalizationCoverageTest.php` scans application and Blade source for literal English strings passed to `__()`, `@lang`, `Flux::toast`, `addError`, and Livewire `#[Title]`.
- The same test file also checks static application translation keys referenced by `__()`, `@lang`, and Livewire `#[Title]` exist in the English language files.
- Commands, URLs, package names, and other executable identifiers can remain literal because they are not translatable prose.

## Restricted hosting

Localization is request/render-time only. It does not require cron, queue workers, Artisan commands, terminal access, external translation APIs, or paid services during normal application usage.

## 2026-06-06 Recheck

Step 016 was rechecked from `steps/step-016-english-localization-and-message-cleanup.md`.

Confirmed and updated:

- The current English language files are `auth`, `automation`, `dashboard`, `maintenance`, `navigation`, `reminders`, `settings`, `setup`, `todos`, and `welcome`.
- Public and authenticated landing pages render localized copy instead of raw translation keys.
- Literal English strings are not passed directly to translation APIs, Flux toasts, `addError`, or Livewire `#[Title]` attributes.
- Added coverage that static translation keys referenced from app and Blade source exist in the English language files.
- Localization remains render-time only and does not depend on cron, queue workers, shell access, external translation services, or paid APIs.

## 2026-06-06 Step 054 Update

Reminder UI text, validation messages, processing reports, preference toasts,
status labels, empty states, and database notification messages live in
`lang/en/reminders.php`. Dashboard and task-toolbar reminder links use existing
dashboard/todo language files.
