# HOW TO RUN

Copy this folder to your Laravel project:

`docs/design-parallel-prompts/`

## To run one agent

Paste this:

```
Read docs/design-parallel-prompts/GLOBAL_DESIGN_RULES.md and docs/design-parallel-prompts/parallel-prompts/prompt-XX-name.md. Execute only this design prompt. Create DESIGN_AGENT_REPORT_XX.md. Do not touch unrelated areas.
```

## To run many agents in parallel

Open separate Codex sessions and assign one prompt file to each session.

Recommended first wave:

- prompt-01-design-audit-and-visual-inventory.md
- prompt-02-design-system-tokens-and-scss-foundation.md
- prompt-03-flux-component-standards.md
- prompt-04-app-shell-navigation-sidebar-topbar-and-responsive-layout.md

Then run page prompts.

Finally run:

- prompt-19-mobile-super-pass.md
- prompt-20-tablet-super-pass.md
- prompt-21-desktop-super-pass.md
- prompt-22-accessibility-super-pass.md
- prompt-25-final-design-merge-qa.md
