# GLOBAL DESIGN UPGRADE RULES — Super Mega Responsive UI/UX

Use this folder as a parallel prompt pack for upgrading the Laravel Todo app design.

This is a DESIGN/UI/UX upgrade pack. Do not rewrite business logic unless a UI bug exposes a security, accessibility, or validation issue.

## Required stack

Use:
- Laravel 13 conventions
- latest Livewire
- Flux v2 as the primary UI component system
- Tailwind CSS 4 as the utility styling system
- clean SCSS only where it helps reusable design tokens, layout helpers, animation helpers, accessibility helpers, or print styles
- no Volt
- no paid UI kits
- no paid APIs
- no external paid design service
- no cron/jobs/artisan dependency for final app behavior

If Volt exists in touched UI, migrate touched UI to normal class-based Livewire components.

## Responsive requirement

Every design change must support:

### Mobile
- 320px to 767px
- touch-friendly controls
- no horizontal overflow
- compact navigation
- readable cards
- collapsible filters
- safe modals/drawers
- thumb-friendly primary actions

### Tablet
- 768px to 1199px
- balanced two-column layouts where useful
- sidebar/drawer decisions must be intentional
- filter panels must not dominate the screen
- cards/lists must remain readable

### Desktop
- 1200px and wider
- comfortable information density
- strong layout hierarchy
- good use of whitespace
- no giant empty wasted screens
- keyboard-friendly productivity flows

## Design quality

The UI must feel like a modern productivity product, not a CRUD demo.

Use consistent:
- spacing
- typography
- buttons
- inputs
- cards
- modals
- dropdowns
- badges
- alerts
- toasts
- tables/lists
- sidebars
- empty states
- loading states
- error states
- success states
- focus states
- danger confirmations

## Accessibility

Every prompt must respect:
- semantic headings
- labels for all inputs
- visible focus states
- keyboard navigation
- accessible modal focus
- enough contrast
- no state relying only on color
- meaningful button names
- safe icon-only controls
- screen-reader-friendly statuses

## Translation

No hardcoded visible text.

All labels, helper text, empty states, buttons, errors, confirmations, badges, toasts, and microcopy must use existing language files and have English keys.

## Parallel work rule

Each agent must:
1. Work only on its assigned area.
2. Avoid changing unrelated business logic.
3. Create/update its own report file:
   `DESIGN_AGENT_REPORT_<NUMBER>.md`
4. List changed files.
5. List possible merge conflicts.
6. List tests/checks run.
7. Keep commits small and meaningful.
8. Never mark another agent's area complete.

## Safety

Do not weaken:
- authentication
- authorization
- private workspace scoping
- collaboration roles
- invite safety
- private attachment access
- import/export privacy
- validation
- translations
- restricted-hosting compatibility

Design must improve the product without breaking security.
