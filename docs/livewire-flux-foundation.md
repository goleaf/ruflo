# Livewire And Flux Foundation

Step 003 establishes Livewire and Flux as the default interactive UI stack.

## 2026-06-06 Recheck

- Livewire is installed at 4.3.1 and Flux UI Free is installed at 2.14.1.
- Full-page app routes use `Route::livewire()` for todos and settings pages.
- The application uses class-based Livewire components under `app/Livewire` with paired Blade views under `resources/views/livewire`.
- Flux components are used for primary forms, buttons, modals, cards, nav, badges, callouts, inputs, selects, dropdowns, toasts, and settings shells.
- The shared layouts include `@fluxAppearance`, `@fluxScripts`, and persisted Flux toast groups.
- Deprecated Flux v1 tags (`flux:option`, `flux:options`, table aliases, and similar renamed components) were not found.
- Manual `@livewireStyles` and `@livewireScripts` directives were not found; Livewire/Flux asset handling stays aligned with the starter-kit layout.
- Recent browser logs contain only an old Vite reconnect message and no current Livewire/Flux runtime errors.

## Guardrails

- New interactive pages should default to class-based Livewire components unless a future step deliberately changes the component format.
- Use Flux free components first for common UI: fields, buttons, modals, nav, cards, badges, tables, pagination, callouts, and toasts.
- Keep client-visible IDs locked or re-resolved server-side, and authorize Livewire actions before mutation.
- Keep visible text and action/validation messages in English language files.
- Do not introduce Volt components.
