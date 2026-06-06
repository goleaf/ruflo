# PARALLEL MERGE MATRIX

## Low-conflict prompts

These usually touch separate pages and can run together:

- Dashboard
- Task list
- Task detail
- Collaboration
- Comments
- Attachments
- Import/export
- Settings
- Admin/maintenance

## High-conflict prompts

These often touch shared layout/components. Run carefully or merge first:

- Design system tokens
- Flux component standards
- App shell/navigation
- Mobile pass
- Tablet pass
- Desktop pass
- Accessibility pass
- Final merge QA

## Merge order

1. Design audit report
2. Design system tokens
3. Flux component standards
4. App shell/navigation
5. Main screens
6. Feature screens
7. Mobile/tablet/desktop passes
8. Accessibility pass
9. Performance/perceived speed pass
10. Final merge QA

## Conflict rule

If two agents modify the same component, keep the version that:
- uses Flux more consistently
- has better accessibility
- has fewer hardcoded strings
- has better responsive behavior
- changes less business logic
- keeps tests passing
