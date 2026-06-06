# DESIGN PROMPT 01 — Design audit and visual inventory

## Goal

Create a full design audit of the current app before major UI changes. Find inconsistent pages, duplicated UI patterns, mobile/tablet/desktop problems, hardcoded text, accessibility gaps, and components that should be replaced by Flux.

This prompt is designed to run in parallel with other design prompts. Work only on this assigned area unless a shared component change is absolutely required.

## Read first

- `../GLOBAL_DESIGN_RULES.md`
- `../PARALLEL_MERGE_MATRIX.md`
- existing project docs and progress files
- current routes/components/views for this area

## Scope boundaries

Do not redesign pages yet except tiny safe fixes. This is mainly audit, inventory, and planning.

## Inspect before changing

Inspect layouts, navigation, dashboard, tasks, projects, filters, reminders, recurrence, collaboration, comments, attachments, import/export, settings, auth pages, admin/maintenance pages, translations, CSS/SCSS, Tailwind usage, and Livewire components.

## Required implementation

Create a design audit report, UI debt list, component inventory, responsive issue list, accessibility issue list, translation issue list, and recommended merge order for other design prompts.

## Mobile requirements

Identify every mobile overflow, cramped form, unusable table, hidden action, difficult tap target, and broken modal/drawer.

## Tablet requirements

Identify pages that waste space or collapse badly on tablet. Recommend two-column or hybrid layouts where useful.

## Desktop requirements

Identify pages with poor hierarchy, too much whitespace, dense clutter, inconsistent grid, or bad alignment.

## Flux, Livewire, Tailwind, SCSS requirements

- Use Flux v2 components where possible.
- Use Livewire for interactive UI state.
- Use Tailwind CSS 4 utilities for layout, spacing, typography, responsiveness, and states.
- Use SCSS only for reusable design helpers that are cleaner than repeating utilities.
- Do not create one-off messy CSS.
- Do not introduce another UI library.
- Do not use Volt.
- If touched UI uses Volt, migrate touched behavior to normal class-based Livewire.
- Keep all visible text translated.
- Do not hardcode labels, buttons, placeholders, empty states, errors, or confirmation text.

## Accessibility requirements

- Every input needs a proper label.
- Every icon-only button needs an accessible name.
- Focus states must be visible.
- Modals/dropdowns must be keyboard usable.
- Status must not rely only on color.
- Error messages must be associated with fields.
- Cards/lists must have meaningful headings or structure.
- Long content must remain readable.
- Loading states must be understandable.
- Empty states must be helpful.

## Security and privacy guardrails

- Do not weaken authorization.
- Do not expose private data through UI previews, dashboard cards, search suggestions, notifications, comments, files, or links.
- Do not show demo credentials outside local/testing/demo.
- Do not turn hidden frontend controls into the only security layer.
- Keep backend policies intact.
- Do not change invite, attachment, export, or admin access rules unless this prompt explicitly touches that area.
- If you discover a security issue, document it in the report and fix it only if the fix is safely within scope.

## Tests and checks

Run available UI/page smoke tests if they exist. If no automated visual tests exist, document manual inspection coverage.

## Required report

Create or update:

`DESIGN_AGENT_REPORT_01.md`

The report must include:

- prompt number and title
- files inspected
- files changed
- mobile changes
- tablet changes
- desktop changes
- Flux components used
- Livewire components touched
- translation keys added/changed
- accessibility improvements
- tests/checks run
- screenshots/manual review notes if available
- possible merge conflicts
- known risks
- exact next recommendation

## Completion checklist

- [ ] Scope respected.
- [ ] Mobile layout checked.
- [ ] Tablet layout checked.
- [ ] Desktop layout checked.
- [ ] Flux components used consistently.
- [ ] Livewire behavior still works.
- [ ] No Volt introduced.
- [ ] No hardcoded visible text.
- [ ] Accessibility basics reviewed.
- [ ] Private data not leaked.
- [ ] Tests/checks run or blockers documented.
- [ ] Report file written.
