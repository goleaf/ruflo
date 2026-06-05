# No Volt Livewire Policy

Step 004 requires the application to use normal Livewire components instead of Volt.

## 2026-06-06 Recheck

- `livewire/volt` is not installed in Composer.
- `php artisan list` does not expose Volt commands.
- No files named for Volt or Livewire v4 emoji component files were found under `resources`, `app`, `routes`, `tests`, or `config`.
- Source scans did not find `livewire/volt`, `Livewire\Volt`, `@volt`, `Volt::`, `make:volt`, or `VoltServiceProvider`.
- Current interactive surfaces use class-based Livewire classes under `app/Livewire` with paired Blade views under `resources/views/livewire`.
- Focused Livewire todo/project/tag/settings tests pass.

## Guardrails

- Do not add Volt as a dependency.
- Do not create single-file Volt components.
- New interactive screens should follow the existing class-based Livewire convention unless a future step explicitly changes the component format.
- If a Volt file ever appears during an upgrade or starter-kit refresh, migrate it to a normal Livewire class plus Blade view before marking the related step complete.
