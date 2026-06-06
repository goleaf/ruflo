# DESIGN PROMPT 06 — Task list super design

## Goal

Upgrade task list UX for scanning, filtering, quick actions, bulk actions, priorities, due dates, recurring badges, shared badges, reminders, comments, attachments, and mobile usability.

This prompt is designed to run in parallel with other design prompts. Work only on this assigned area unless a shared component change is absolutely required.

## Read first

- `../GLOBAL_DESIGN_RULES.md`
- `../PARALLEL_MERGE_MATRIX.md`
- existing project docs and progress files
- current routes/components/views for this area

## Scope boundaries

Touch task list and related task row/card components. Avoid task detail redesign except shared task card component impact.

## Inspect before changing

Inspect task list, task row/card, pagination, empty states, loading states, filters, sorting, search, bulk toolbar, quick complete/archive/delete actions.

## Required implementation

Create polished task cards/rows, responsive density modes, clear metadata badges, quick actions, safe dropdowns, inline states, and helpful empty/search states.

## Mobile requirements

Use card layout with primary action visible, secondary actions in dropdown, filters collapsible, bulk toolbar sticky or clear.

## Tablet requirements

Support compact cards or two-column list if useful, with filter side panel option.

## Desktop requirements

Support dense but readable rows/cards, persistent filters, keyboard-friendly actions, and clear sorting.

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

Test list for empty, many tasks, overdue, completed, archived, shared, recurring, and bulk selection states.

## Required report

Create or update:

`DESIGN_AGENT_REPORT_06.md`

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
