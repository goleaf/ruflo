# Todo Master Prompt Pack

This pack is designed for Codex / AI coding agent work on a Laravel Todo application.

It contains:

- `MASTER_PROMPT.md` — one big prompt to start the whole long-running process
- `GLOBAL_RULES.md` — modern stack, restricted hosting, demo/seeding/invite/request rules
- `steps/step-01...step-15.md` — separate step prompts
- `checklists/FINAL_RELEASE_CHECKLIST.md`
- `checklists/SECURITY_PRIVACY_CHECKLIST.md`
- `checklists/WEB_ONLY_HOSTING_CHECKLIST.md`
- `progress-templates/` — files the agent must create/update inside your repository

## How to use

1. Copy this whole folder into your project, for example:
   `docs/todo-master-plan/`

2. Open `MASTER_PROMPT.md`.

3. Paste the full master prompt into Codex from the root of your Laravel project.

4. Let the agent work step by step.

5. If the session stops, restart Codex and paste:
   `Continue from docs/todo-master-plan/MASTER_PROMPT.md and the progress files. Do not redo completed work. Read TODO_MASTER_PROGRESS.md first.`

## Important

No AI agent can truly guarantee 20-200 hours of uninterrupted execution. This pack forces the agent to write progress files and commits after every stable step so work can continue after interruption.
