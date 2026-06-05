# Frontend Design System

## Step 005 Scope

Step 005 standardizes the asset layer around Tailwind CSS 4, Flux UI, and a small SCSS support entry.

## Asset Entries

- `resources/css/app.css` remains the primary Tailwind CSS 4 and Flux entry.
- `resources/scss/app.scss` is a secondary Vite entry for reusable CSS that is easier to maintain in SCSS.
- `resources/js/app.js` remains the main JavaScript entry.
- `resources/js/passkeys.js` remains a separate passkey entry loaded only by passkey components.

## Tailwind CSS 4 Rules

- Use `@import "tailwindcss"` and CSS-first `@theme` tokens.
- Keep Flux styles imported from `vendor/livewire/flux`.
- Use `@source` entries for Blade views, Laravel pagination views, and Flux stubs.
- Do not add a legacy `tailwind.config.js` unless a future feature has a clear need.
- Do not use deprecated Tailwind v3 utilities such as `bg-opacity-*`, `text-opacity-*`, `flex-shrink-*`, or `flex-grow-*`.

## SCSS Rules

SCSS is intentionally small and supports Tailwind rather than replacing it.

Current partials:

- `_tokens.scss` defines CSS custom properties used by shared surfaces and focus styles.
- `_accessibility.scss` provides skip-link, focus-ring, screen-reader context, and reduced-motion helpers.
- `_surfaces.scss` provides reusable background/surface helpers that are awkward as repeated inline CSS.
- `_print.scss` provides print visibility and document-safe print defaults.

New component styling should still prefer Flux components and Tailwind utility classes. Add SCSS only for repeated global behavior, print output, browser accessibility primitives, or reusable non-component effects.

## Verification

The frontend asset pipeline is guarded by `tests/Feature/FrontendAssetPipelineTest.php` and by `npm run build`.

## 2026-06-06 Recheck

Step 005 was rechecked from the root prompt pack and `steps/step-005-tailwind-css-4-and-scss-design-layer.md`.

Confirmed:

- Tailwind CSS 4.3, `@tailwindcss/vite` 4.3, Vite 8, Laravel Vite plugin 3.1, and `sass-embedded` 1.100 are installed.
- `vite.config.js` keeps `resources/css/app.css` and `resources/scss/app.scss` as separate entries.
- Shared and welcome heads load both CSS entries through `@vite`.
- Runtime CSS/SCSS sources contain no deprecated Tailwind v3 utility syntax.
- SCSS remains limited to tokens, accessibility helpers, shared surface helpers, and print rules.
- `tests/Feature/FrontendAssetPipelineTest.php` and `npm run build` pass.
