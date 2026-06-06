# START HERE — Run Design Prompts in Parallel

Copy this folder into your Laravel project, for example:

`docs/design-parallel-prompts/`

Then run prompts from `parallel-prompts/` in separate Codex sessions.

## Recommended parallel strategy

Use separate branches/workspaces if possible:

- `design/01-audit`
- `design/02-design-system`
- `design/03-mobile-shell`
- `design/04-dashboard`
- etc.

If separate branches are not available, run only non-overlapping prompts at the same time.

## Best run order

### Wave 1 — Foundation
Run first:
- Prompt 01 — Design audit
- Prompt 02 — Design system tokens
- Prompt 03 — Flux component standards
- Prompt 04 — App shell and navigation

### Wave 2 — Main product screens
Can run in parallel after Wave 1:
- Prompt 05 — Dashboard
- Prompt 06 — Task list
- Prompt 07 — Task detail
- Prompt 08 — Forms
- Prompt 09 — Filters/search/bulk
- Prompt 10 — Kanban/calendar

### Wave 3 — Feature screens
Can run in parallel:
- Prompt 11 — Reminders/notifications
- Prompt 12 — Recurring tasks
- Prompt 13 — Collaboration/invites/members
- Prompt 14 — Comments/mentions
- Prompt 15 — Attachments
- Prompt 16 — Import/export
- Prompt 17 — Settings
- Prompt 18 — Admin/maintenance

### Wave 4 — Responsive and polish
Run after page work:
- Prompt 19 — Mobile pass
- Prompt 20 — Tablet pass
- Prompt 21 — Desktop pass
- Prompt 22 — Accessibility pass
- Prompt 23 — Microcopy/states
- Prompt 24 — Performance/perceived speed
- Prompt 25 — Final merge QA

## Important

Do not let an agent write compressed progress like “other design prompts remaining”.

Every prompt has its own file, its own report, and its own completion checklist.
